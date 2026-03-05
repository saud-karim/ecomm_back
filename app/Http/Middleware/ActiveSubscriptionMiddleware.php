<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ActiveSubscriptionMiddleware
{
    /**
     * Ensure the authenticated seller has an active subscription.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'seller') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $seller = $user->seller;

        if (!$seller) {
            return response()->json([
                'success' => false,
                'message' => 'Seller profile not found.',
            ], 404);
        }

        if (!$seller->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'You need an active subscription to access this feature.',
                'code'    => 'SUBSCRIPTION_REQUIRED',
            ], 402);
        }

        if (!$seller->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Your seller account is pending approval.',
                'code'    => 'APPROVAL_PENDING',
            ], 403);
        }

        // Attach seller to request for easy access in controllers
        $request->merge(['_seller' => $seller]);

        return $next($request);
    }
}
