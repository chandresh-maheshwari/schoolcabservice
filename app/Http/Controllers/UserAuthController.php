<?php
namespace App\Http\Controllers;

use App\Helpers\IdEncoder;
use App\Helpers\ImageHelper;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserAuthController extends Controller
{
    public function showSchoolLogin(string $schoolSlug)
    {
        $schoolSlug = trim($schoolSlug);
        if ($schoolSlug === '' || in_array(strtolower($schoolSlug), ['admin', 'login', 'logout', 'homepage', 'dashboard', 'cms'], true)) {
            abort(404);
        }

        $school = School::where('slug', $schoolSlug)->where('deleted', 0)->first();
        if (! $school) {
            abort(404);
        }

        return view('auth.school-login', [
            'schoolSlug' => $schoolSlug,
            'schoolName' => $school->school_name,
        ]);
    }

    public function loginSchool(Request $request, string $schoolSlug)
    {
        $request->merge([
            'source'      => 'admin',
            'school_slug' => $schoolSlug,
        ]);

        return $this->loginuser($request);
    }

    public function loginuser(Request $request)
    {
        try {
            $loginValue = (string) $request->input('login', $request->input('email', ''));
            $password   = (string) $request->input('password', '');

            $userQuery = User::query();
            if (filter_var($loginValue, FILTER_VALIDATE_EMAIL)) {
                $userQuery->where('email', $loginValue);
            } else {
                $userQuery->where('username', $loginValue);
            }

            $user = $userQuery->first();

            if (! $user) {
                return response()->json([
                    'errors' => [
                        'login' => ['Invalid username/email.'],
                    ],
                ], 422);
            }

            if ($user->deleted == 1) {
                return response()->json([
                    'errors' => [
                        'login' => ['Invalid username/email.'],
                    ],
                ], 422);
            }

            if (! Hash::check($password, $user->password)) {
                return response()->json([
                    'errors' => [
                        'password' => ['The password is incorrect.'],
                    ],
                ], 422);
            }

            $source = $request->input('source', 'front');
            if ($source === 'admin') {
                $roleName = strtolower((string) optional($user->role)->name);
                $isAllowedAdminLogin = $user->isAdmin() || $user->isSchool() || $roleName === 'super admin';

                if (! $isAllowedAdminLogin) {
                    return response()->json([
                        'errors' => [
                            'login' => ['Only admin or school users can login to this system.'],
                        ],
                    ], 422);
                }

                if ($roleName === 'school') {
                    $schoolSlug = trim((string) $request->input('school_slug', ''));
                    if ($schoolSlug === '') {
                        return response()->json([
                            'errors' => [
                                'school_slug' => ['School login URL is required.'],
                            ],
                        ], 422);
                    }

                    $school = School::where('slug', $schoolSlug)
                        ->where('deleted', 0)
                        ->where('user_id', $user->id)
                        ->first();

                    if (! $school) {
                        return response()->json([
                            'errors' => [
                                'login' => ['Invalid school URL for this user.'],
                            ],
                        ], 422);
                    }
                }
            }

            try {
                $token = JWTAuth::fromUser($user);
            } catch (JWTException $e) {
                Log::error('JWTException: ' . $e->getMessage());
                return response()->json(['error' => 'Could not create token'], 500);
            }

            Auth::login($user);
            Session::flash('login_success', 'Login successful');

            $redirectUrl = '/admin/dashboard';
            if ($source === 'admin') {
                $roleName = strtolower((string) optional($user->role)->name);
                if ($roleName === 'school') {
                    $schoolSlug = trim((string) $request->input('school_slug', ''));
                    $redirectUrl = $schoolSlug !== '' ? '/' . $schoolSlug . '/dashboard' : '/admin/dashboard';
                }
            }

            return response()->json([
                'message' => 'Login successful',
                'token'   => $token,
                 'redirect_url' => $redirectUrl,
            ]);
        } catch (Exception $e) {
            $line = $e->getLine();
            errorLog($e);
            return response()->json([
                'error' => 'An error occurred. Please try again later.',
            ], 500);
        }
    }

    protected function authenticated(Request $request, $user)
    {
        try {

            return redirect()->intended('/dashboard');
        } catch (Exception $e) {
            $line = $e->getLine();
            errorLog($e);
            return $e->getMessage();
        }
    }

    public function logoutperform(Request $request)
    {
        $impersonatorId = $request->session()->get('impersonator_id');
        if ($impersonatorId) {
            $impersonator = User::where('id', $impersonatorId)->where('deleted', 0)->first();
            if ($impersonator && method_exists($impersonator, 'isAdmin') && $impersonator->isAdmin()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Auth::login($impersonator);

                return redirect()->route('admin_layout.index')
                    ->with('success', 'Back to admin account.');
            }
        }

        $user = Auth::user();
        $schoolSlug = null;
        if ($user && method_exists($user, 'isSchool') && $user->isSchool()) {
            $schoolSlug = School::where('deleted', 0)->where('user_id', $user->id)->value('slug');
            $schoolSlug = trim((string) $schoolSlug);
            if ($schoolSlug === '') {
                $schoolSlug = null;
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // If a school user logs out, take them back to their school login URL.
        if ($schoolSlug !== null) {
            return redirect()->route('school.slug.login.page', ['schoolSlug' => $schoolSlug]);
        }

        return redirect()->route('login');
    }

    public function frontlogoutperform(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function deleteImage($id)
    {
        $user = User::findOrFail($id);
        if ($user->photo) {
            $imagePath = public_path($user->photo);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
            $user->photo = null;
            $user->save();
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
        return response()->json(['success' => false, 'message' => 'No image to delete.'], 404);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'mobile'     => 'nullable|digits_between:10,11',
            'photo'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email'      => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('deleted', 0);
                }),
            ],
            'password'   => [
                'required',
                'string',
                'min:8',
                'max:15',
                'regex:/^(?=.*[0-9])(?=.*[\W_]).+$/',
            ],
            'role_id'    => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'mobile'     => $request->mobile,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'role_id'    => $request->role_id,
        ]);

        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'user_' . $user->id . '.' . $extension;
            $tmpPath   = $image->getRealPath();
            $destDir   = public_path('storage/profile_pictures');
            $destPath  = $destDir . '/' . $imageName;

            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            $size    = [92, 92];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.',
                ]);
            }

            $data['photo'] = 'storage/profile_pictures/' . $imageName;
            $user->update($data);
        }

        return response()->json(['success' => true, 'message' => 'User registered Successfully.']);
    }

    public function getAuthenticatedUser(Request $request)
    {
        try {
            $user = Auth::user();
            Log::info('Authenticated user:', ['user' => $user]);

            if ($user) {
                return response()->json($user);
            } else {
                return response()->json(['error' => 'User not authenticated'], 401);
            }
        } catch (\Exception $e) {
            Log::error('Error retrieving authenticated user', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Could not retrieve user'], 500);
        }
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name'  => 'sometimes|required|string|max:255',
            'mobile'     => 'nullable|digits_between:10,11',
            'photo'      => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email'      => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) {
                    return $query->where('deleted', 0);
                }),
            ],
            'password'   => 'nullable|string|min:8',
            'role_id'    => 'sometimes|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($id);
        $data = $request->except('image');

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'user_' . $user->id . '.' . $extension;
            $tmpPath   = $image->getRealPath();
            $destDir   = public_path('storage/profile_pictures');
            $destPath  = $destDir . '/' . $imageName;

            if (! file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            $size    = [92, 92];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.',
                ]);
            }

            $data['photo'] = 'storage/profile_pictures/' . $imageName;
        }
        $user->update($data);
        return response()->json(['success' => true, 'message' => 'User updated Successfully.']);
    }

    public function deleteUser(Request $request, $id)
    {
        $user          = User::findOrFail($id);
        $user->deleted = 1;
        $user->save();

        return response()->json(['success' => true, 'message' => 'User deleted Successfully.']);
    }

    public function show($id)
    {
        $decodedId = IdEncoder::decode($id);
        $user      = User::findOrFail($decodedId);
        return view('my_account.show', compact('user'));
    }

    public function changePassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'max:15',
                'regex:/^(?=.*[0-9])(?=.*[\W_]).+$/',
                'confirmed',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($id);

        if (! Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Old password is incorrect.'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['success' => true, 'message' => 'Password changed Successfully.']);
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['errors' => ['email' => ['This email is not registered.']]], 422);
        }

        $otp = rand(100000, 999999);

        Otp::updateOrCreate(
            ['user_id' => $user->id],
            ['otp' => $otp]
        );

        Mail::to($user->email)->send(new OtpMail($otp));
        return response()->json(['message' => 'OTP sent Successfully']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|numeric',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['errors' => ['email' => ['This email is not registered.']]], 422);
        }

        $otpRecord = Otp::where('user_id', $user->id)->where('otp', $request->otp)->first();

        if (! $otpRecord) {
            return response()->json(['errors' => ['otp' => ['Invalid OTP.']]], 422);
        }
        return response()->json(['message' => 'OTP verified Successfully']);
    }

    public function resetnewPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email'       => 'required|email',
            'newPassword' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['errors' => ['email' => ['This email is not registered.']]], 422);
        }

        $otpRecord = Otp::where('user_id', $user->id)->first();
        if (! $otpRecord) {
            return response()->json(['errors' => ['otp' => ['OTP verification required.']]], 422);
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();
        $otpRecord->delete();
        return response()->json(['message' => 'Password reset successful']);
    }

    public function getDashboardStats()
    {
        try {
            $websiteVisits = DB::table('website_visits')->count();

            $authorRoleId = Role::where('name', 'Author')->value('id');
            $writerRoleId = Role::where('name', 'Writer')->value('id');

            $authorCount = User::where('role_id', $authorRoleId)->where('deleted', 0)->count();
            $writerCount = User::where('role_id', $writerRoleId)->where('deleted', 0)->count();

            $blogCount = DB::table('blogs')->where('deleted', 0)->count();

            $quoteCount = DB::table('quotes')->where('deleted', 0)->count();

            $magazineCount = DB::table('magazines')->where('deleted', 0)->count();

            return response()->json([
                'success' => true,
                'data'    => [
                    'website_visits' => $websiteVisits,
                    'authors'        => $authorCount,
                    'writers'        => $writerCount,
                    'blogs'          => $blogCount,
                    'quotes'         => $quoteCount,
                    'magazines'      => $magazineCount,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching dashboard stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dashboard statistics',
            ], 500);
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            if (! $token) {
                Log::warning('Token refresh attempted without token');
                return response()->json(['error' => 'Token not provided'], 401);
            }

            try {
                JWTAuth::checkOrFail();
                $newToken = JWTAuth::refresh($token);

                Log::info('Token refreshed Successfully', ['user_id' => Auth::id()]);

                return response()->json([
                    'success' => true,
                    'token'   => $newToken,
                    'message' => 'Token refreshed Successfully',
                ]);
            } catch (JWTException $e) {
                Log::error('JWT Exception during token refresh: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'error'   => 'Could not refresh token',
                    'message' => 'Your session has expired. Please log in again.',
                ], 401);
            }
        } catch (Exception $e) {
            Log::error('Unexpected error during token refresh: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'An error occurred',
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    private function hashUserId($id)
    {
        $salt = bin2hex(random_bytes(4));
        $data = $id . '|' . $salt;
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function unhashUserId($hash)
    {
        $decoded = base64_decode(strtr($hash, '-_', '+/'));
        if (! $decoded) {
            return null;
        }

        list($id, $salt) = explode('|', $decoded);

        return $id;
    }

    public function toggleUserStatus($id)
    {
        try {
            $user = User::find($id);

            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $user->status = $user->status == 1 ? 0 : 1;
            $user->save();

            $statusText = $user->status == 1 ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "User account has been {$statusText} Successfully",
                'status' => $user->status,
            ]);
        } catch (Exception $e) {
            $line = $e->getLine();
            errorLog('toggleUserStatus', 'Error', $e->getMessage(), $e->getCode(), $e->getFile() . '-Line No: ' . $line);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating user status',
            ], 500);
        }
    }

    public function getUserContentCounts($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'success' => true,
            'data'    => [
                'user_name' => $user->first_name . ' ' . $user->last_name,
            ],
        ]);
    }

    public function deleteUserAndData($id)
    {
        $user = User::findOrFail($id);

        $user->deleted = 1;
        $user->save();

        return response()->json(['success' => true, 'message' => 'User and all related data deleted Successfully.']);
    }
}
