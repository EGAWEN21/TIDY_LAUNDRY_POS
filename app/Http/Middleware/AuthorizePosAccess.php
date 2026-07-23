<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizePosAccess
{
    /**
     * Ensure the authenticated user is authorized to operate the POS.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission('order_create')) {
            abort(403, 'You are not authorized to access the POS.');
        }

        if ($user->currentAccessToken() && ! $user->tokenCan('pos:access')) {
            abort(403, 'This token is not authorized to access the POS.');
        }

        return $next($request);
    }
}
