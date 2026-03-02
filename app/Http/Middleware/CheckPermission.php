<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/');
        }

        $routeName = Route::currentRouteName();
        
        if (!$routeName) {
            return $next($request);
        }

        if (!Auth::user()->canAccessAdminRoute($routeName)) {
            return redirect()->route('admin_layout.index')->with('error', 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
