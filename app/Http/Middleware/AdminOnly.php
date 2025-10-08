<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Check if user is an admin
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Access denied. Administrator privileges required.');
        }

        // Check if email is verified
        if (!Auth::user()->hasVerifiedEmail()) {
            abort(403, 'Email verification required for admin access.');
        }

        return $next($request);
    }
}