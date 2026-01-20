<?php

namespace App\Http\Controllers;

use AddressAccountInformation;
use App\Models\AccountInformationModel;
use App\Models\AddressAccountInformationModel;
use App\Models\ProductModel;
use App\Models\ProdutPromocodeModel;
use App\Models\PromocodeModel;
use App\Models\UserAddressInformationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\FacadesValidator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Role;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\DB;
use Spatie\Analytics\AnalyticsFacade as Analytics;
use Spatie\Analytics\Period;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\Dimension;
use App\Helpers\IdEncoder;
use App\Helpers\ImageHelper;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\FacadesLog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserAuthController extends Controller
{


    public function loginuser(Request $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            $user = User::where('email', $credentials['email'])->first();

            if (!$user) {
                return response()->json([
                    'errors' => [
                        'email' => ['This email is not registered.']
                    ]
                ], 422);
            }

            if ($user->deleted == 1) {
                return response()->json([
                    'errors' => [
                        'email' => ['This email is not registered.']
                    ]
                ], 422);
            }

            if (!Hash::check($credentials['password'], $user->password)) {
                return response()->json([
                    'errors' => [
                        'password' => ['The password is incorrect.']
                    ]
                ], 422);
            }

            // if ($user->status == 0) {
            //     return response()->json([
            //         'errors' => [
            //             'email' => ['Your account is under review by administrator. We will get back to you soon!']
            //         ]
            //     ], 422);
            // }

            $source = $request->input('source', 'front');

            if ($source === 'admin') {
                $superAdminRole = Role::where('name', 'Super Admin')->first();
                // dd((string) $user->role_id);
                if (!$superAdminRole || (string) $user->role_id !== (string) $superAdminRole->id) {
                    return response()->json([
                        'errors' => [
                            'email' => ['Only super admin can login to this system.']
                        ]
                    ], 422);
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

            return response()->json([
                'message' => 'Login successful',
                'token' => $token
            ]);
        } catch (Exception $e) {
            $line = $e->getLine();
            errorLog($e);
            return response()->json([
                'error' => 'An error occurred. Please try again later.'
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



    public function logoutperform()
    {
        Session::flush();
        Auth::logout();
        return redirect('/admin/login');
    }

    public function frontlogoutperform()
    {
        Session::flush();
        Auth::logout();
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
            'last_name' => 'required|string|max:255',
            'mobile' => 'nullable|digits:10',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) {
                    return $query->where('deleted', 0);
                }),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:15',
                'regex:/^(?=.*[0-9])(?=.*[\W_]).+$/'
            ],
            'role_id' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // $photoPath = null;
        // if ($request->hasFile('photo')) {
        //     $photoName = $request->file('photo')->getClientOriginalName();
        //     $photoPath = $request->file('photo')->storeAs('profile_pictures', $photoName, 'public');
        // }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'mobile' => $request->mobile,
            // 'photo' => $photoPath,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'user_' . $user->id . '.' . $extension;
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/profile_pictures');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [92, 92];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            // dd($success);
            if (!$success) {
                // $user->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }

            $data['photo'] = 'storage/profile_pictures/' . $imageName;
            $user->update($data);
        }
        // dd($user);

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

    // public function logout(Request $request)
    // {
    //     try {
    //         JWTAuth::invalidate(JWTAuth::getToken());
    //         return response()->json(['success' => true, 'message' => 'Successfully logged out.']);
    //     } catch (JWTException $e) {
    //         return response()->json(['error' => 'Could not log out, please try again.'], 500);
    //     }
    // }

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
            'last_name' => 'sometimes|required|string|max:255',
            'mobile' => 'sometimes|required|digits:10',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) {
                    return $query->where('deleted', 0);
                }),
            ],
            'password' => 'nullable|string|min:8',
            'role_id' => 'sometimes|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // if ($request->hasFile('photo')) {
        //     $photoName = $request->file('photo')->getClientOriginalName();
        //     $photoPath = $request->file('photo')->storeAs('profile_pictures', $photoName, 'public');
        //     $user->photo = $photoPath;
        // }

        // $user->first_name = $request->input('first_name', $user->first_name);
        // $user->last_name = $request->input('last_name', $user->last_name);
        // $user->mobile = $request->input('mobile', $user->mobile);
        // $user->email = $request->input('email', $user->email);
        // $user->role_id = $request->input('role_id', $user->role_id);

        $user = User::findOrFail($id);
        $data = $request->except('image');


        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            $imageName = 'user_' . $user->id . '.' . $extension;
            $tmpPath = $image->getRealPath();
            $destDir = public_path('storage/profile_pictures');
            $destPath = $destDir . '/' . $imageName;

            // Make sure the directory exists
            if (!file_exists($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Crop and resize
            $size = [92, 92];
            $success = ImageHelper::cropAndResize($tmpPath, $destPath, $size[0], $size[1]);
            // dd($success);
            if (!$success) {
                // $user->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Image must be at least ' . $size[0] . 'x' . $size[1] . ' pixels and a valid image type.'
                ]);
            }

            $data['photo'] = 'storage/profile_pictures/' . $imageName;
        }
        $user->update($data);

        // $user->save();

        return response()->json(['success' => true, 'message' => 'User updated Successfully.']);
    }

    public function deleteUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->deleted = 1;
        $user->save();

        return response()->json(['success' => true, 'message' => 'User deleted Successfully.']);
    }


    public function show($id)
    {
        $decodedId = IdEncoder::decode($id);
        $user = User::findOrFail($decodedId);
        return view('my_account.show', compact('user'));
    }

    public function getUserDataById($id)
    {
        try {
            $user = User::findOrFail($id);

            try {
                $authUser = JWTAuth::parseToken()->authenticate();
            } catch (\Exception $e) {
                $authUser = null;
            }

            $followers = $user->followers ?? [];
            $following = $user->following ?? [];

            $followers = array_map('intval', $followers);
            $following = array_map('intval', $following);

            $followers = array_filter($followers, function ($followerId) use ($user) {
                return $followerId != $user->id;
            });
            $following = array_filter($following, function ($followingId) use ($user) {
                return $followingId != $user->id;
            });

            $followers = User::whereIn('id', $followers)->where('deleted', 0)->pluck('id')->toArray();
            $following = User::whereIn('id', $following)->where('deleted', 0)->pluck('id')->toArray();

            $isFollowing = false;
            if ($authUser) {
                $authUserFollowing = $authUser->following ?? [];
                $authUserFollowing = array_map('intval', $authUserFollowing);
                $isFollowing = in_array($id, $authUserFollowing);
            }

            // $blogsCount = \App\Models\Blog::where('user_id', $id)->where('status', 'approved')->where('deleted', 0)->count();
            // $magazinesCount = \App\Models\Magazine::where('user_id', $id)->where('deleted', 0)->count();
            // $quotesCount = \App\Models\Quote::where('user_id', $id)->where('deleted', 0)->count();

            return response()->json([
                'success' => true,
                'data' => $user,
                'followers' => $followers,
                'following' => $following,
                'isFollowing' => $isFollowing,
                'followers_count' => count($followers),
                'following_count' => count($following),
                // 'blogs_count' => $blogsCount,
                // 'magazines_count' => $magazinesCount,
                // 'quotes_count' => $quotesCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user data', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    public function getUserFollowers($id)
    {
        try {
            $user = User::findOrFail($id);
            $followerIds = $user->followers;

            $followerIds = array_filter($followerIds, function ($followerId) use ($id) {
                return $followerId != $id;
            });


            $followers = User::whereIn('id', $followerIds)
                ->where('deleted', 0)
                ->get();

            $followers = $followers->map(function ($follower) {
                $follower->encoded_user_id = \App\Helpers\IdEncoder::encode($follower->id);
                return $follower;
            });

            return response()->json(['success' => true, 'followers' => $followers]);
        } catch (\Exception $e) {
            Log::error('Error fetching followers', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Could not fetch followers'], 500);
        }
    }

    public function getUserFollowing($id)
    {
        try {
            $user = User::findOrFail($id);
            $followingIds = $user->following;

            $followingIds = array_filter($followingIds, function ($followingId) use ($id) {
                return $followingId != $id;
            });

            $following = User::whereIn('id', $followingIds)
                ->where('deleted', 0)
                ->get();

            $following = $following->map(function ($following) {
                $following->encoded_user_id = \App\Helpers\IdEncoder::encode($following->id);
                return $following;
            });

            return response()->json(['success' => true, 'following' => $following]);
        } catch (\Exception $e) {
            Log::error('Error fetching following', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Could not fetch following'], 500);
        }
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
                'confirmed'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::findOrFail($id);

        if (!Hash::check($request->old_password, $user->password)) {
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
        if (!$user) {
            return response()->json(['errors' => ['email' => ['This email is not registered.']]], 422);
        }

        $otp = rand(100000, 999999);

        Otp::updateOrCreate(
            ['user_id' => $user->id],
            ['otp' => $otp]
        );

        // Send OTP to user's email
        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json(['message' => 'OTP sent Successfully']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['errors' => ['email' => ['This email is not registered.']]], 422);
        }

        $otpRecord = Otp::where('user_id', $user->id)->where('otp', $request->otp)->first();

        if (!$otpRecord) {
            return response()->json(['errors' => ['otp' => ['Invalid OTP.']]], 422);
        }

        // // Optionally, you can delete the OTP after successful verification
        // $otpRecord->delete();

        return response()->json(['message' => 'OTP verified Successfully']);
    }

    // public function resetnewPassword(Request $request)
    // {
    //     // dd('ok');
    //     // $request->validate([
    //     //     'email' => 'required|email',
    //     //     'newPassword' => 'required|string|min:8|confirmed'
    //     // ]);
    //     // dd('ok');
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email',
    //         'newPassword' => 'required|string|min:8|confirmed',
    //         // 'confirmPassword' => 'required|string|min:8|confirmed'
    //     ]);
    //     dd($validator->email);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $user = User::where('email', $request->email)->first();
    //     if (!$user) {
    //         return response()->json(['errors' => ['email' => ['This email is not registered.']]], 422);
    //     }

    //     $otpRecord = Otp::where('user_id', $user->id)->first();
    //     if (!$otpRecord) {
    //         return response()->json(['errors' => ['otp' => ['OTP verification required.']]], 422);
    //     }

    //     $user->passwrod = Hash::make($request->newPassword);
    //     $user->save();

    //     $otpRecord->delete();

    //     return response()->json(['message' => 'Password reset successful']);
    // }


    //     public function resetnewPassword(Request $request)
    // {
    //     // Validate the request inputs, including the confirmPassword field
    //     $validator = Validator::make($request->all(), [
    //         'email' => 'required|email',
    //         'newPassword' => 'required|string|min:8|confirmed', // This checks for 'newPassword' and 'newPassword_confirmation'
    //     ]);

    //     // If validation fails, return the error messages
    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     // Check if the user exists by the provided email
    //     $user = User::where('email', $request->email)->first();
    //     if (!$user) {
    //         return response()->json(['errors' => ['email' => ['This email is not registered.']]], 422);
    //     }

    //     // Check if an OTP record exists for the user
    //     $otpRecord = Otp::where('user_id', $user->id)->first();
    //     if (!$otpRecord) {
    //         return response()->json(['errors' => ['otp' => ['OTP verification required.']]], 422);
    //     }

    //     // Update the user's password with the new hashed password
    //     $user->password = Hash::make($request->newPassword);
    //     $user->save();

    //     // Delete the OTP record as it is no longer needed
    //     $otpRecord->delete();

    //
    // Return a success message
    //     return response()->json(['message' => 'Password reset successful']);
    // }


    public function resetnewPassword(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'newPassword' => 'required|string|min:8|confirmed',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['errors' => ['email' => ['This email is not registered.']]], 422);
        }


        $otpRecord = Otp::where('user_id', $user->id)->first();
        if (!$otpRecord) {
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
                'data' => [
                    'website_visits' => $websiteVisits,
                    'authors' => $authorCount,
                    'writers' => $writerCount,
                    'blogs' => $blogCount,
                    'quotes' => $quoteCount,
                    'magazines' => $magazineCount,
                    // 'expert_users' => $authorCount + $writerCount,
                    // 'total_content' => $blogCount + $quoteCount + $magazineCount,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching dashboard stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching dashboard statistics'
            ], 500);
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token) {
                Log::warning('Token refresh attempted without token');
                return response()->json(['error' => 'Token not provided'], 401);
            }

            try {
                // Verify the token is still valid
                JWTAuth::checkOrFail();

                // Generate new token
                $newToken = JWTAuth::refresh($token);

                Log::info('Token refreshed Successfully', ['user_id' => Auth::id()]);

                return response()->json([
                    'success' => true,
                    'token' => $newToken,
                    'message' => 'Token refreshed Successfully'
                ]);
            } catch (JWTException $e) {
                Log::error('JWT Exception during token refresh: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'error' => 'Could not refresh token',
                    'message' => 'Your session has expired. Please log in again.'
                ], 401);
            }
        } catch (Exception $e) {
            Log::error('Unexpected error during token refresh: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'An error occurred',
                'message' => 'An unexpected error occurred. Please try again later.'
            ], 500);
        }
    }

    public function getAnalyticsData()
    {
        $analyticsData = Analytics::fetchVisitorsAndPageViews(Period::days(7));

        return response()->json(['success' => true, 'data' => $analyticsData]);
    }

    public function getVisitorCountApi()
    {
        try {
            $credentialsPath = config('services.google_analytics.credentials_path');
            $viewId = config('services.google_analytics.view_id');

            $client = new \Google\Analytics\Data\V1beta\BetaAnalyticsDataClient([
                'credentials' => base_path($credentialsPath),
            ]);

            $response = $client->runReport([
                'property' => 'properties/' . $viewId,
                'dateRanges' => [
                    new \Google\Analytics\Data\V1beta\DateRange([
                        'start_date' => '30daysAgo',
                        'end_date' => 'today',
                    ]),
                ],
                'metrics' => [
                    new \Google\Analytics\Data\V1beta\Metric(['name' => 'activeUsers']),
                ],
            ]);

            $visitorCount = $response->getRows()[0]->getMetricValues()[0]->getValue();

            return response()->json(['success' => true, 'visitor_count' => $visitorCount]);
        } catch (\Exception $e) {
            Log::error('Error fetching visitor count: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching visitor count'], 500);
        }
    }

    public function getAnalyticsKey()
    {
        $analyticsKey = config('services.google_analytics.key');
        return response()->json(['key' => $analyticsKey]);
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
        if (!$decoded) return null;
        list($id, $salt) = explode('|', $decoded);

        return $id;
    }

    public function toggleUserStatus($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Toggle the status (0 to 1 or 1 to 0)
            $user->status = $user->status == 1 ? 0 : 1;
            $user->save();

            $statusText = $user->status == 1 ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "User account has been {$statusText} Successfully",
                'status' => $user->status
            ]);
        } catch (Exception $e) {
            $line = $e->getLine();
            errorLog('toggleUserStatus', 'Error', $e->getMessage(), $e->getCode(), $e->getFile() . '-Line No: ' . $line);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating user status'
            ], 500);
        }
    }

    public function getAllAuthors()
    {
        $authorRoleId = Role::where('name', 'Author')->value('id');
        $authors = User::where('role_id', $authorRoleId)
            ->where('deleted', 0)
            ->where('status', 1)
            ->select('id', 'first_name', 'last_name', 'email', 'photo', 'role_id', 'followers', 'following')
            ->get();
        $authors = $authors->map(function ($author) {
            $author->blog_count = DB::table('blogs')->where('user_id', $author->id)->where('deleted', 0)->count();
            $author->quote_count = DB::table('quotes')->where('user_id', $author->id)->where('deleted', 0)->count();
            $author->magazine_count = DB::table('magazines')->where('user_id', $author->id)->where('deleted', 0)->count();
            $author->encoded_user_id = \App\Helpers\IdEncoder::encode($author->id);
            return $author;
        });
        return response()->json(['data' => $authors]);
    }

    public function getUserContentCounts($id)
    {
        $user = User::findOrFail($id);
        // $blogCount = \App\Models\Blog::where('user_id', $id)->where('deleted', 0)->count();
        // $quoteCount = \App\Models\Quote::where('user_id', $id)->where('deleted', 0)->count();
        // $magazineCount = \App\Models\Magazine::where('user_id', $id)->where('deleted', 0)->count();

        return response()->json([
            'success' => true,
            'data' => [
                // 'blog_count' => $blogCount,
                // 'quote_count' => $quoteCount,
                // 'magazine_count' => $magazineCount,
                'user_name' => $user->first_name . ' ' . $user->last_name,
            ]
        ]);
    }

    public function deleteUserAndData($id)
    {
        $user = User::findOrFail($id);

        // \App\Models\Blog::where('user_id', $id)->update(['deleted' => 1]);
        // \App\Models\Quote::where('user_id', $id)->update(['deleted' => 1]);
        // \App\Models\Magazine::where('user_id', $id)->update(['deleted' => 1]);

        $user->deleted = 1;
        $user->save();

        return response()->json(['success' => true, 'message' => 'User and all related data deleted Successfully.']);
    }
}
