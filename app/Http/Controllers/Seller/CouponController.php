<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    private function seller()
    {
        return auth()->user()->seller;
    }

    /** GET /seller/coupons */
    public function index(): JsonResponse
    {
        $coupons = $this->seller()->coupons()->latest()->get();

        return response()->json(['success' => true, 'data' => $coupons]);
    }

    /** POST /seller/coupons */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'            => 'nullable|string|unique:coupons,code|max:30',
            'discount_type'   => 'required|in:percent,fixed',
            'discount_value'  => 'required|numeric|min:0',
            'min_order_amount'=> 'nullable|numeric|min:0',
            'max_discount'    => 'nullable|numeric|min:0',
            'usage_limit'     => 'nullable|integer|min:1',
            'expires_at'      => 'nullable|date|after:today',
        ]);

        $validated['code']      = strtoupper($validated['code'] ?? Str::random(8));
        $validated['is_active'] = true;

        $coupon = $this->seller()->coupons()->create($validated);

        return response()->json([
            'success' => true,
            'message' => "Coupon '{$coupon->code}' created.",
            'data'    => $coupon,
        ], 201);
    }

    /** PUT /seller/coupons/{id} */
    public function update(Request $request, Coupon $coupon): JsonResponse
    {
        $this->authorizeCoupon($coupon);

        $validated = $request->validate([
            'discount_type'   => 'sometimes|in:percent,fixed',
            'discount_value'  => 'sometimes|numeric|min:0',
            'min_order_amount'=> 'nullable|numeric|min:0',
            'max_discount'    => 'nullable|numeric|min:0',
            'usage_limit'     => 'nullable|integer|min:1',
            'expires_at'      => 'nullable|date',
            'is_active'       => 'boolean',
        ]);

        $coupon->update($validated);

        return response()->json(['success' => true, 'message' => 'Coupon updated.', 'data' => $coupon]);
    }

    /** DELETE /seller/coupons/{id} */
    public function destroy(Coupon $coupon): JsonResponse
    {
        $this->authorizeCoupon($coupon);
        $coupon->delete();

        return response()->json(['success' => true, 'message' => 'Coupon deleted.']);
    }

    private function authorizeCoupon(Coupon $coupon): void
    {
        if ($coupon->seller_id !== $this->seller()->id) {
            abort(403, 'Access denied.');
        }
    }
}
