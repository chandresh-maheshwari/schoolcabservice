<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectSchoolFromAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user || ! method_exists($user, 'isSchool') || ! $user->isSchool()) {
            return $next($request);
        }

        $schoolSlug = School::where('deleted', 0)->where('user_id', $user->id)->value('slug');
        $schoolSlug = trim((string) $schoolSlug);
        if ($schoolSlug === '') {
            return $next($request);
        }

        // Convert `/admin/<path>` -> `/<slug>/<path>`
        $path = ltrim((string) $request->path(), '/');
        if (str_starts_with($path, 'admin/')) {
            $suffix = substr($path, strlen('admin/'));
            $target = '/' . $schoolSlug . '/' . $suffix;

            if ($request->getQueryString()) {
                $target .= '?' . $request->getQueryString();
            }

            return redirect($target);
        }

        return $next($request);
    }
}

