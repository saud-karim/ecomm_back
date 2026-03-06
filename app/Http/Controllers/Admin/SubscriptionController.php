<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    /** GET /admin/subscriptions */
    public function index(Request $request): JsonResponse
    {
        $subscriptions = Subscription::with('seller.user', 'plan')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->plan_id, fn($q) => $q->where('plan_id', $request->plan_id))
            ->when($request->search, fn($q) =>
                $q->whereHas('seller', fn($sq) =>
                    $sq->where('store_name_en', 'like', "%{$request->search}%")
                       ->orWhere('store_name_ar', 'like', "%{$request->search}%")
                )
            )
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data'    => $subscriptions,
        ]);
    }

    /** GET /admin/subscriptions/{id} */
    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load('seller.user', 'plan');

        return response()->json([
            'success' => true,
            'data'    => $subscription,
        ]);
    }
}
