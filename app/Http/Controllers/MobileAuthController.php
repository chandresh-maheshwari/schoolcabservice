<?php

namespace App\Http\Controllers;

use App\Mail\MobileLoginOtpMail;
use App\Models\Driver;
use App\Models\Otp;
use App\Models\Parents;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $login = trim((string) ($request->input('login') ?: $request->input('email') ?: ''));
        $password = (string) $request->input('password', '');
        $requestedRole = $this->normalizeRequestedRole($request->input('role'));

        if ($login === '' || $password === '') {
            return response()->json(['message' => 'Login and password are required'], 422);
        }

        $match = $this->resolveAuthUserByIdentifier($login, $requestedRole, false);
        if (! $match) {
            return response()->json([
                'message' => $this->isEmailLogin($login)
                    ? 'No active mobile user found with this email'
                    : 'No active mobile user found with this mobile number',
            ], 404);
        }

        if (! $this->passwordMatches($password, (string) ($match['user']->password ?? ''))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'role' => $match['role'],
            'email' => $match['email'],
        ]);
    }

    public function sendEmailOtp(Request $request): JsonResponse
    {
        $login = trim((string) ($request->input('login') ?: $request->input('email') ?: ''));
        $password = (string) $request->input('password', '');
        $requestedRole = $this->normalizeRequestedRole($request->input('role'));

        if ($login === '' || $password === '') {
            return response()->json(['message' => 'Login and password are required'], 422);
        }

        $match = $this->resolveAuthUserByIdentifier($login, $requestedRole, false);
        if (! $match) {
            return response()->json([
                'message' => $this->isEmailLogin($login)
                    ? 'No active mobile user found with this email'
                    : 'No active mobile user found with this mobile number',
            ], 404);
        }

        if (! $this->passwordMatches($password, (string) ($match['user']->password ?? ''))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $otp = (string) random_int(100000, 999999);
        Otp::updateOrCreate(['user_id' => $match['user']->id], ['otp' => $otp]);

        Mail::to($match['email'])->send(new MobileLoginOtpMail($otp, $match['role'], 'mobile-login'));

        return response()->json([
            'message' => 'OTP sent successfully',
            'email' => $match['email'],
            'delivery' => 'email',
        ]);
    }

    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $login = trim((string) ($request->input('login') ?: $request->input('email') ?: ''));
        $otp = trim((string) $request->input('otp', ''));
        $requestedRole = $this->normalizeRequestedRole($request->input('role'));

        if ($login === '' || $otp === '') {
            return response()->json(['message' => 'Login/email and OTP are required'], 422);
        }

        $match = $this->resolveAuthUserByIdentifier($login, $requestedRole, false);
        if (! $match) {
            return response()->json(['message' => 'No active mobile user found for OTP verification'], 404);
        }

        $otpRecord = Otp::query()
            ->where('user_id', $match['user']->id)
            ->where('otp', $otp)
            ->first();

        if (! $otpRecord || $this->otpExpired($otpRecord->created_at)) {
            return response()->json(['message' => 'OTP verification failed'], 401);
        }

        $otpRecord->delete();

        return response()->json([
            'message' => 'OTP verified successfully',
            'role' => $match['role'],
            'email' => $match['email'],
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $email = trim((string) $request->input('email', ''));
        if ($email === '') {
            return response()->json(['message' => 'Email is required'], 422);
        }

        $match = $this->resolveAuthUserByIdentifier($email, null, false);
        if (! $match || strcasecmp((string) $match['email'], $email) !== 0) {
            return response()->json([
                'message' => 'There is no account with the provided email ID, for register contact administrator.',
            ], 404);
        }

        $otp = (string) random_int(100000, 999999);
        Otp::updateOrCreate(['user_id' => $match['user']->id], ['otp' => $otp]);
        Mail::to($match['email'])->send(new MobileLoginOtpMail($otp, $match['role'], 'forgot-password'));

        return response()->json(['message' => 'OTP sent successfully']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $email = trim((string) $request->input('email', ''));
        $otp = trim((string) $request->input('otp', ''));
        $newPassword = (string) $request->input('newPassword', '');

        if ($email === '' || $otp === '' || $newPassword === '') {
            return response()->json(['message' => 'Email, OTP and new password are required'], 422);
        }

        $match = $this->resolveAuthUserByIdentifier($email, null, false);
        if (! $match || strcasecmp((string) $match['email'], $email) !== 0) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $otpRecord = Otp::query()
            ->where('user_id', $match['user']->id)
            ->where('otp', $otp)
            ->first();

        if (! $otpRecord || $this->otpExpired($otpRecord->created_at)) {
            return response()->json(['message' => 'OTP verification failed'], 401);
        }

        $match['user']->password = Hash::make($newPassword);
        $match['user']->save();
        $otpRecord->delete();

        return response()->json(['message' => 'Password reset successfully']);
    }

    private function resolveAuthUserByIdentifier(string $login, ?string $requestedRole, bool $includeInactive): ?array
    {
        $roles = $requestedRole ? [$requestedRole] : ['driver', 'parent', 'admin'];

        foreach ($roles as $role) {
            $match = match ($role) {
                'driver' => $this->matchDriver($login, $includeInactive),
                'parent' => $this->matchParent($login, $includeInactive),
                'admin' => $this->matchUser($login, 'admin', $includeInactive),
                default => null,
            };

            if ($match) {
                return $match;
            }
        }

        return null;
    }

    private function matchDriver(string $login, bool $includeInactive): ?array
    {
        $query = Driver::query()->with(['loginUser.role', 'user.role']);

        if (! $includeInactive) {
            $query->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })->where('status', 1);
        }

        if ($this->isEmailLogin($login)) {
            $query->where(function ($driverQuery) use ($login) {
                $driverQuery
                    ->whereHas('loginUser', function ($q) use ($login) {
                        $q->where('email', $login);
                    })
                    ->orWhereHas('user', function ($q) use ($login) {
                        $q->where('email', $login);
                    });
            });
        } else {
            $query->where('driver_phone', $login);
        }

        $driver = $query->latest('id')->first();
        $loginUser = $driver?->loginUser ?: $driver?->user;
        if (! $driver || ! $loginUser) {
            return null;
        }

        return [
            'user' => $loginUser,
            'role' => 'driver',
            'email' => (string) $loginUser->email,
        ];
    }

    private function matchParent(string $login, bool $includeInactive): ?array
    {
        $query = Parents::query()->with('loginUser.role');

        if (! $includeInactive) {
            $query->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })->where('status', 1);
        }

        if ($this->isEmailLogin($login)) {
            $query->where(function ($q) use ($login) {
                $q->where('email', $login)
                    ->orWhereHas('loginUser', function ($loginUserQuery) use ($login) {
                        $loginUserQuery->where('email', $login);
                    });
            });
        } else {
            $query->where('contact_number', $login);
        }

        $parent = $query->latest('id')->first();
        $loginUser = $parent?->loginUser;
        if (! $parent || ! $loginUser) {
            return null;
        }

        return [
            'user' => $loginUser,
            'role' => 'parent',
            'email' => (string) ($loginUser->email ?: $parent->email),
        ];
    }

    private function matchUser(string $login, string $roleName, bool $includeInactive): ?array
    {
        $query = User::query()->with('role');

        if (! $includeInactive && method_exists(User::class, 'where')) {
            $query->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            });
        }

        if ($this->isEmailLogin($login)) {
            $query->where('email', $login);
        } else {
            $query->where('mobile', $login);
        }

        $user = $query->latest('id')->first();
        if (! $user) {
            return null;
        }

        $resolvedRole = $this->normalizeRequestedRole(optional($user->role)->name);
        if ($resolvedRole !== $roleName) {
            return null;
        }

        return [
            'user' => $user,
            'role' => $roleName,
            'email' => (string) $user->email,
        ];
    }

    private function normalizeRequestedRole($value): ?string
    {
        $role = strtolower(trim((string) $value));
        if ($role === '') {
            return null;
        }

        if ($role === 'super admin') {
            return 'admin';
        }

        return $role;
    }

    private function isEmailLogin(string $value): bool
    {
        return str_contains($value, '@');
    }

    private function passwordMatches(string $plain, string $stored): bool
    {
        if ($stored === '') {
            return false;
        }

        if (str_starts_with($stored, '$2')) {
            return Hash::check($plain, $stored);
        }

        return hash_equals($stored, $plain);
    }

    private function otpExpired($createdAt): bool
    {
        if (! $createdAt) {
            return true;
        }

        return now()->diffInMinutes($createdAt) > 10;
    }
}
