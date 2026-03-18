<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FrontendApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        $configuredKey = (string) config('services.frontend_api.key', '');

        if ($configuredKey === '') {
            return response()->json([
                'success' => false,
                'message' => 'Frontend API key is not configured.',
            ], 500);
        }

        $providedKey = (string) (
            $request->header('X-Frontend-Api-Key')
            ?? $request->header('X-API-KEY')
            ?? $request->input('api_key', '')
        );

        if (! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid frontend API key.',
            ], 401);
        }

        return $next($request);
    }
}
