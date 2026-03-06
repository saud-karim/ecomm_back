<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SellerController extends Controller
{
    /** GET /admin/sellers */
    public function index(Request $request): JsonResponse
    {
        $sellers = Seller::with('user', 'subscription.plan')
            ->withCount('products')
            ->when($request->is_approved !== null, fn($q) =>
                $q->where('is_approved', $request->boolean('is_approved'))
            )
            ->when($request->search, fn($q) =>
                $q->where('store_name_en', 'like', "%{$request->search}%")
                  ->orWhere('store_name_ar', 'like', "%{$request->search}%")
                  ->orWhereHas('user', fn($uq) =>
                    $uq->where('email', 'like', "%{$request->search}%")
                  )
            )
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data'    => $sellers,
        ]);
    }

    /** GET /admin/sellers/{id} */
    public function show(Seller $seller): JsonResponse
    {
        $seller->load('user', 'subscription.plan', 'products', 'orders');

        return response()->json([
            'success' => true,
            'data'    => $seller,
        ]);
    }

    /** PUT /admin/sellers/{id}/approve */
    public function approve(Seller $seller): JsonResponse
    {
        $seller->update([
            'is_approved'      => true,
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Seller '{$seller->store_name}' approved successfully.",
            'data'    => $seller,
        ]);
    }

    /** PUT /admin/sellers/{id}/reject */
    public function reject(Request $request, Seller $seller): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $seller->update([
            'is_approved'      => false,
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Seller '{$seller->store_name}' rejected.",
            'data'    => $seller,
        ]);
    }

    /** DELETE /admin/sellers/{id} */
    public function destroy(Seller $seller): JsonResponse
    {
        $seller->delete();

        return response()->json([
            'success' => true,
            'message' => 'Seller removed.',
        ]);
    }
}
