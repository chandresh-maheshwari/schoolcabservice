<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateWithJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            if (Auth::check()) {
                return $next($request);
            }

            $token = JWTAuth::getToken();
            if (! $token) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $user = JWTAuth::setToken($token)->authenticate();
            if (! $user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            Auth::shouldUse('api');
            Auth::setUser($user);
        } catch (JWTException $e) {
            Log::warning('JWT authentication failed: ' . $e->getMessage());
            return response()->json(['error' => 'User not authenticated'], 401);
        } catch (Exception $e) {
            Log::error('Authentication Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Authentication error'], 500);
        }

        return $next($request);
    }
}
