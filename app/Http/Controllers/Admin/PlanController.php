<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    /** GET /admin/plans */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::withCount('subscriptions')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $plans,
        ]);
    }

    /** POST /admin/plans */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'price'         => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'max_products'  => 'nullable|integer|min:1',
            'max_offers'    => 'nullable|integer|min:1',
            'features'      => 'nullable|array',
            'is_featured'   => 'boolean',
            'sort_order'    => 'integer',
        ]);

        $plan = SubscriptionPlan::create([
            ...$validated,
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully.',
            'data'    => $plan,
        ], 201);
    }

    /** PUT /admin/plans/{id} */
    public function update(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $validated = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'price'         => 'sometimes|numeric|min:0',
            'billing_cycle' => 'sometimes|in:monthly,yearly',
            'max_products'  => 'nullable|integer|min:1',
            'max_offers'    => 'nullable|integer|min:1',
            'features'      => 'nullable|array',
            'is_active'     => 'boolean',
            'is_featured'   => 'boolean',
            'sort_order'    => 'integer',
        ]);

        $plan->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Plan updated.',
            'data'    => $plan,
        ]);
    }

    /** DELETE /admin/plans/{id} */
    public function destroy(SubscriptionPlan $plan): JsonResponse
    {
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a plan with active subscriptions.',
            ], 409);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Plan deleted.',
        ]);
    }
}
