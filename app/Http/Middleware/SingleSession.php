<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SingleSession
{
    /**
     * Enforce single-session login per role.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = Session::getId();

            if ($user->user_type == 1) {
                $roleSessionId = \Illuminate\Support\Facades\Cache::get('role_session_admin');
            } else {
                $roleSessionId = \Illuminate\Support\Facades\Cache::get('role_session_role_' . $user->role_id);
            }

            // If the role's stored session doesn't match this one,
            // someone else logged in with this role — force logout here
            if ($roleSessionId && $roleSessionId !== $currentSessionId) {
                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                return redirect()->route('login')->withErrors([
                    'login_error' => 'Another user with your role has logged in from another device. You have been logged out.',
                ]);
            }
        }

        return $next($request);
    }
}
