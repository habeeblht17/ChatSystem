<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $roleName = $user->role?->name ?? null;
        

        if ($roleName !== UserRole::Admin->value || $user->is_suspended === true || $user->is_active === false) {
            abort(403, 'Unauthorized: You do not have access!');
        }
        
        return $next($request);
    }
}
