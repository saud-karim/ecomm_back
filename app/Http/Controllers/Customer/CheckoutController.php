<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Coupon;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * POST /customer/checkout
     *
     * Creates order(s) from cart items.
     * Groups items by seller → one Order per seller.
     * Validates coupon if provided.
     * Returns orders (in production: triggers payment gateway).
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'address_id'    => 'required|exists:addresses,id',
            'coupon_code'   => 'nullable|string',
            'notes'         => 'nullable|string|max:500',
        ]);

        $userId = auth()->id();

        // Verify address belongs to user
        $address = Address::where('user_id', $userId)->findOrFail($request->address_id);

        // Get cart items
        $cartItems = CartItem::where('user_id', $userId)
            ->with('product.seller', 'variant')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Cart is empty.'], 422);
        }

        // Validate coupon
        $coupon       = null;
        $couponDiscount = 0;

        if ($request->coupon_code) {
            $coupon = Coupon::where('code', strtoupper($request->coupon_code))
                ->where('is_active', true)
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
                ->first();

            if (!$coupon) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired coupon.'], 422);
            }

            if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
                return response()->json(['success' => false, 'message' => 'Coupon usage limit reached.'], 422);
            }
        }

        try {
            DB::beginTransaction();

            // Group cart items by seller
            $grouped = $cartItems->groupBy(fn($item) => $item->product->seller_id);
            $orders  = [];

            foreach ($grouped as $sellerId => $items) {
                $subtotal = $items->sum(fn($item) =>
                    ($item->variant?->price ?? $item->product->price) * $item->quantity
                );

                // Apply coupon only if it belongs to this seller or is platform-wide
                $discount = 0;
                if ($coupon && ($coupon->seller_id === $sellerId || $coupon->seller_id === null)) {
                    $discount = $coupon->calculateDiscount($subtotal);
                }

                $total = max(0, $subtotal - $discount);

                $order = Order::create([
                    'customer_id'     => $userId,
                    'seller_id'       => $sellerId,
                    'address_id'      => $address->id,
                    'coupon_id'       => $coupon?->id,
                    'status'          => 'pending',
                    'payment_status'  => 'pending',
                    'payment_method'  => 'tap',
                    'subtotal'        => round($subtotal, 2),
                    'discount_amount' => round($discount, 2),
                    'total'           => round($total, 2),
                    'notes'           => $request->notes,
                ]);

                foreach ($items as $cartItem) {
                    $price = $cartItem->variant?->price ?? $cartItem->product->price;
                    OrderItem::create([
                        'order_id'      => $order->id,
                        'product_id'    => $cartItem->product_id,
                        'variant_id'    => $cartItem->variant_id,
                        'product_name'  => app()->getLocale() === 'ar'
                            ? $cartItem->product->name_ar
                            : $cartItem->product->name_en,
                        'product_image' => $cartItem->product->primaryImage?->image_url,
                        'price'         => $price,
                        'quantity'      => $cartItem->quantity,
                        'subtotal'      => round($price * $cartItem->quantity, 2),
                    ]);
                }

                $orders[] = $order->load('items');
            }

            // Increment coupon usage
            if ($coupon) {
                $coupon->increment('used_count');
            }

            // Clear cart
            CartItem::where('user_id', $userId)->delete();

            DB::commit();

            // TODO: Integrate tap.company payment — return payment_url
            return response()->json([
                'success'      => true,
                'message'      => 'Orders placed successfully.',
                'data'         => [
                    'orders'       => $orders,
                    'payment_note' => 'Payment gateway integration coming soon.',
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Checkout failed. Please try again.'], 500);
        }
    }

    /**
     * POST /customer/checkout/validate-coupon
     * Quick coupon validation before checkout.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'code'    => 'required|string',
            'amount'  => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', strtoupper($request->code))
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()))
            ->first();

        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired coupon.'], 404);
        }

        $discount = $coupon->calculateDiscount($request->amount);

        return response()->json([
            'success'  => true,
            'data'     => [
                'coupon'         => $coupon,
                'discount_amount'=> round($discount, 2),
                'final_amount'   => round(max(0, $request->amount - $discount), 2),
            ],
        ]);
    }
}
