<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Tymon\JWTAuth\Facades\JWTAuth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function resolveActorUserId(?Request $request = null): ?int
    {
        $request = $request ?: request();

        $currentUserId = Auth::id();

        if (! $currentUserId) {
            $sessionUserId = Session::get('userid');
            if (is_numeric($sessionUserId) && (int) $sessionUserId > 0) {
                $currentUserId = (int) $sessionUserId;
            }
        }

        if (! $currentUserId) {
            $headerUserId = $request->header('X-Auth-User-Id');
            if (is_numeric($headerUserId) && (int) $headerUserId > 0) {
                $currentUserId = (int) $headerUserId;
            }
        }

        if (! $currentUserId) {
            $inputUserId = $request->input('user_id');
            if (is_numeric($inputUserId) && (int) $inputUserId > 0) {
                $currentUserId = (int) $inputUserId;
            }
        }

        if (! $currentUserId) {
            try {
                if ($request->bearerToken()) {
                    $jwtUser = JWTAuth::parseToken()->authenticate();
                    if ($jwtUser) {
                        Auth::setUser($jwtUser);
                        $currentUserId = (int) $jwtUser->id;
                    }
                }
            } catch (\Throwable $e) {
                $currentUserId = null;
            }
        }

        return $currentUserId ?: null;
    }
}
