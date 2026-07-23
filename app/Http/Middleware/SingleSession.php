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
     * Enforce one active web session per user.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $currentSessionId = Session::getId();

            if ($user->current_session_id && $user->current_session_id !== $currentSessionId) {
                Auth::logout();
                Session::invalidate();
                Session::regenerateToken();

                return redirect()->route('login')->withErrors([
                    'login_error' => 'Your account was signed in from another device. You have been logged out.',
                ]);
            }
        }

        return $next($request);
    }
}
