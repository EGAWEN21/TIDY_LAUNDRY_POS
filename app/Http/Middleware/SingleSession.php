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
     * Enforce single-session login.
     * If the user's stored session ID doesn't match the current session,
     * it means they logged in from another device/browser — so log this session out.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = Session::getId();

            // If the user's stored session doesn't match this one,
            // someone else logged in with this account — force logout here
            if ($user->current_session_id && $user->current_session_id !== $currentSessionId) {
                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                return redirect()->route('login')->withErrors([
                    'login_error' => 'Your account was logged in from another device. You have been logged out.',
                ]);
            }
        }

        return $next($request);
    }
}
