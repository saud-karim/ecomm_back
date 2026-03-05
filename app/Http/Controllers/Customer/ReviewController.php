<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    /** POST /customer/reviews */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id'   => 'required|exists:orders,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string|max:1000',
        ]);

        $userId = auth()->id();

        // Verify order belongs to customer and contains the product
        $order = Order::where('id', $validated['order_id'])
            ->where('customer_id', $userId)
            ->where('status', 'delivered')
            ->firstOrFail();

        $hasProduct = $order->items()->where('product_id', $validated['product_id'])->exists();
        if (!$hasProduct) {
            return response()->json(['success' => false, 'message' => 'You can only review products you have purchased.'], 403);
        }

        // Prevent duplicate review
        $exists = Review::where('product_id', $validated['product_id'])
            ->where('customer_id', $userId)
            ->where('order_id', $validated['order_id'])
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'You have already reviewed this product for this order.'], 409);
        }

        $review = Review::create([
            ...$validated,
            'customer_id' => $userId,
            'is_approved' => true, // Auto-approve. Admin can moderate later.
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted. Thank you!',
            'data'    => $review,
        ], 201);
    }

    /** GET /customer/reviews */
    public function myReviews(): JsonResponse
    {
        $reviews = Review::where('customer_id', auth()->id())
            ->with('product:id,name_en,name_ar', 'product.primaryImage')
            ->latest()
            ->paginate(10);

        return response()->json(['success' => true, 'data' => $reviews]);
    }
}
