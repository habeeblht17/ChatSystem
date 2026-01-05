<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminUserMiddleware
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

        $user->loadMissing('role');

        $roleName = $user->role?->name;
        $allowed = [
            UserRole::Admin->value,
            UserRole::User->value,
        ];

        // Deny if the user has no allowed role, is suspended or not active.
        $isAllowedRole = is_string($roleName) && in_array($roleName, $allowed, true);
        $isSuspended = (bool) ($user->is_suspended ?? false);
        $isActive = (bool) ($user->is_active ?? false);

        if (! $isAllowedRole || $isSuspended || ! $isActive) {
            
            abort(403, 'Unauthorized: You do not have access!');
        }

        return $next($request);
    }
}
