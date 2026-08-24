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
        $registeredEmail = $this->normalizeRegisteredEmail($request->input('registeredEmail'), $login);
        $password = (string) $request->input('password', '');
        $requestedRole = $this->normalizeRequestedRole($request->input('role'));

        if ($login === '' || $password === '') {
            return response()->json(['message' => 'Login and password are required'], 422);
        }

        if ($requestedRole !== null && ! in_array($requestedRole, ['driver', 'parent'], true)) {
            return response()->json(['message' => 'A valid mobile role is required'], 422);
        }

        $match = $this->resolveAuthUserByIdentifier($login, $requestedRole, $registeredEmail, false);
        if (! $match) {
            $inactiveMatch = $this->resolveAuthUserByIdentifier($login, $requestedRole, $registeredEmail, true);
            if ($inactiveMatch && $this->isInactiveMatch($inactiveMatch, $registeredEmail)) {
                return response()->json(['message' => 'This mobile account is inactive. Please contact admin to reactivate it.'], 403);
            }

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
        $registeredEmail = $this->normalizeRegisteredEmail($request->input('registeredEmail'), $login);
        $password = (string) $request->input('password', '');
        $requestedRole = $this->normalizeRequestedRole($request->input('role'));

        if ($login === '' || $password === '') {
            return response()->json(['message' => 'Login and password are required'], 422);
        }

        if ($requestedRole !== null && ! in_array($requestedRole, ['driver', 'parent'], true)) {
            return response()->json(['message' => 'A valid mobile role is required'], 422);
        }

        $match = $this->resolveAuthUserByIdentifier($login, $requestedRole, $registeredEmail, false);
        if (! $match) {
            $inactiveMatch = $this->resolveAuthUserByIdentifier($login, $requestedRole, $registeredEmail, true);
            if ($inactiveMatch && $this->isInactiveMatch($inactiveMatch, $registeredEmail)) {
                return response()->json(['message' => 'This mobile account is inactive. Please contact admin to reactivate it.'], 403);
            }

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
        $registeredEmail = $this->normalizeRegisteredEmail($request->input('registeredEmail'), $login);
        $otp = trim((string) $request->input('otp', ''));
        $requestedRole = $this->normalizeRequestedRole($request->input('role'));

        if ($login === '' || $otp === '') {
            return response()->json(['message' => 'Login/email and OTP are required'], 422);
        }

        $match = $this->resolveAuthUserByIdentifier($login, $requestedRole, $registeredEmail, false);
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

        $match = $this->resolveAuthUserByIdentifier($email, null, $email, false);
        if (! $match) {
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

        $match = $this->resolveAuthUserByIdentifier($email, null, $email, false);
        if (! $match) {
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

    private function resolveAuthUserByIdentifier(string $login, ?string $requestedRole, string $registeredEmail = '', bool $includeInactive = false): ?array
    {
        $roles = $requestedRole ? [$requestedRole] : ['driver', 'parent'];

        foreach ($roles as $role) {
            $match = $role === 'driver'
                ? $this->matchDriver($login, $registeredEmail, $includeInactive)
                : $this->matchParent($login, $registeredEmail, $includeInactive);

            if ($match) {
                return $match;
            }
        }

        return null;
    }

    private function matchDriver(string $login, string $registeredEmail, bool $includeInactive): ?array
    {
        $query = Driver::query()->with('user');

        if (! $includeInactive) {
            $query->where(function ($q) {
                $q->where('deleted', 0)->orWhereNull('deleted');
            })->where('status', 1);
        }

        if ($this->isEmailLogin($login)) {
            $query->whereHas('user', function ($q) use ($login) {
                $q->where('email', $login);
            });
        } else {
            $query->where('driver_phone', $login);
            if ($registeredEmail !== '') {
                $query->whereHas('user', function ($q) use ($registeredEmail) {
                    $q->where('email', $registeredEmail);
                });
            }
        }

        $driver = $query->latest('id')->first();
        if (! $driver || ! $driver->user) {
            return null;
        }

        if (! $includeInactive && ! $this->isUserActive($driver->user)) {
            return null;
        }

        return [
            'user' => $driver->user,
            'role' => 'driver',
            'email' => (string) $driver->user->email,
            'inactive' => ! $this->isDriverActive($driver) || ! $this->isUserActive($driver->user),
        ];
    }

    private function matchParent(string $login, string $registeredEmail, bool $includeInactive): ?array
    {
        $query = Parents::query()->with('loginUser');

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
            if ($registeredEmail !== '') {
                $query->where(function ($q) use ($registeredEmail) {
                    $q->where('email', $registeredEmail)
                        ->orWhereHas('loginUser', function ($loginUserQuery) use ($registeredEmail) {
                            $loginUserQuery->where('email', $registeredEmail);
                        });
                });
            }
        }

        $parent = $query->latest('id')->first();
        $loginUser = $parent?->loginUser;
        if (! $parent || ! $loginUser) {
            return null;
        }

        if (! $includeInactive && ! $this->isUserActive($loginUser)) {
            return null;
        }

        return [
            'user' => $loginUser,
            'role' => 'parent',
            'email' => (string) ($loginUser->email ?: $parent->email),
            'inactive' => ! $this->isParentActive($parent) || ! $this->isUserActive($loginUser),
        ];
    }

    private function isInactiveMatch(array $match, string $registeredEmail): bool
    {
        if (! ($match['inactive'] ?? false)) {
            return false;
        }

        if ($registeredEmail === '') {
            return true;
        }

        return strcasecmp((string) ($match['email'] ?? ''), $registeredEmail) === 0;
    }

    private function isDriverActive(Driver $driver): bool
    {
        return (int) ($driver->deleted ?? 0) === 0 && (int) ($driver->status ?? 0) === 1;
    }

    private function isParentActive(Parents $parent): bool
    {
        return (int) ($parent->deleted ?? 0) === 0 && (int) ($parent->status ?? 0) === 1;
    }

    private function isUserActive(User $user): bool
    {
        return (int) ($user->deleted ?? 0) === 0 && (int) ($user->status ?? 0) === 1;
    }

    private function isEmailLogin(string $value): bool
    {
        return str_contains($value, '@');
    }

    private function normalizeRegisteredEmail($registeredEmail, string $login): string
    {
        $normalized = trim((string) $registeredEmail);
        if ($normalized !== '' && str_contains($normalized, '@')) {
            return mb_strtolower($normalized);
        }

        return $this->isEmailLogin($login) ? mb_strtolower($login) : '';
    }

    private function normalizeRequestedRole($role): ?string
    {
        $normalized = mb_strtolower(trim((string) $role));
        if ($normalized === '') {
            return null;
        }

        return $normalized === 'super admin' ? 'admin' : $normalized;
    }

    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        if (str_starts_with($storedPassword, '$2')) {
            return Hash::check($plainPassword, $storedPassword);
        }

        return hash_equals($storedPassword, $plainPassword);
    }

    private function otpExpired($createdAt): bool
    {
        if (! $createdAt) {
            return true;
        }

        $expiryMinutes = (int) env('EMAIL_OTP_EXPIRY_MINUTES', 10);
        return now()->diffInMinutes($createdAt) >= max($expiryMinutes, 1);
    }
}
