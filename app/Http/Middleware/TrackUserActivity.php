<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $expiresAt = now()->addMinutes(5);
            
            // Update cache to mark user as online
            Cache::put("user-is-online-{$user->id}", true, $expiresAt);
            
            // Update last_seen_at every 2 minutes to reduce DB writes
            $lastUpdated = Cache::get("user-last-seen-updated-{$user->id}");
            
            if (!$lastUpdated || now()->diffInMinutes($lastUpdated) >= 2) {
                $user->last_seen_at = now();
                $user->save();
                
                Cache::put("user-last-seen-updated-{$user->id}", now(), now()->addMinutes(2));
            }
        }
        
        return $next($request);
    }
}
