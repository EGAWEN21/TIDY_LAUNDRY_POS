<?php

namespace App\Http\Middleware;

use App\ExpenseHelper;
use App\Livewire\Installer\InstallController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && (Auth::user()->user_type == 1 || Auth::user()->user_type == null)) {
            return $next($request);
        }
        abort(403, 'Unauthorized Access. Admins only.');
    }
}