<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    private function seller()
    {
        return auth()->user()->seller;
    }

    /** GET /seller/subscription/plans */
    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json(['success' => true, 'data' => $plans]);
    }

    /** GET /seller/subscription/current */
    public function current(): JsonResponse
    {
        $subscription = $this->seller()->activeSubscription()->with('plan')->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'has_active' => !is_null($subscription),
                'subscription' => $subscription,
            ],
        ]);
    }

    /** POST /seller/subscription/subscribe */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id'     => 'required|exists:subscription_plans,id',
        ]);

        $seller = $this->seller();
        $plan   = SubscriptionPlan::findOrFail($request->plan_id);

        // Cancel current active subscription
        $seller->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);

        $billing_days = $plan->billing_cycle === 'yearly' ? 365 : 30;

        // In production: payment gateway call here → get payment_ref
        // For now: auto-activate (integrate tap.company in next phase)
        $subscription = $seller->subscriptions()->create([
            'plan_id'     => $plan->id,
            'status'      => 'active',
            'starts_at'   => now(),
            'expires_at'  => now()->addDays($billing_days),
            'amount_paid' => $plan->price,
            'payment_ref' => 'PENDING_PAYMENT', // Will be replaced by tap.company ref
        ]);

        return response()->json([
            'success' => true,
            'message' => "Subscribed to {$plan->name_en} plan.",
            'data'    => $subscription->load('plan'),
        ], 201);
    }

    /** GET /seller/subscription/history */
    public function history(): JsonResponse
    {
        $history = $this->seller()->subscriptions()->with('plan')->latest()->get();

        return response()->json(['success' => true, 'data' => $history]);
    }
}
