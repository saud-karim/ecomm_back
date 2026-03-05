<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage in routes: middleware('role:super_admin')
     *                  middleware('role:seller,super_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Insufficient permissions.',
            ], 403);
        }

        return $next($request);
    }
}
