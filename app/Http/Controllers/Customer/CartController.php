<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    private function userId(): int
    {
        return auth()->id();
    }

    /** GET /customer/cart */
    public function index(): JsonResponse
    {
        $items = CartItem::where('user_id', $this->userId())
            ->with('product.primaryImage', 'variant')
            ->get()
            ->map(function ($item) {
                $price = $item->variant?->price ?? $item->product->price;
                return [
                    'id'           => $item->id,
                    'product'      => $item->product,
                    'variant'      => $item->variant,
                    'quantity'     => $item->quantity,
                    'unit_price'   => $price,
                    'subtotal'     => round($price * $item->quantity, 2),
                ];
            });

        $total = $items->sum('subtotal');

        return response()->json([
            'success' => true,
            'data'    => [
                'items'       => $items,
                'total'       => round($total, 2),
                'items_count' => $items->sum('quantity'),
            ],
        ]);
    }

    /** POST /customer/cart */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id'  => 'required|exists:products,id',
            'variant_id'  => 'nullable|exists:product_variants,id',
            'quantity'    => 'required|integer|min:1',
        ]);

        $product = Product::approved()->findOrFail($request->product_id);

        // Check variant belongs to product
        if ($request->variant_id) {
            $variant = ProductVariant::where('product_id', $product->id)->findOrFail($request->variant_id);
        }

        // Upsert cart item
        $item = CartItem::firstOrNew([
            'user_id'    => $this->userId(),
            'product_id' => $product->id,
            'variant_id' => $request->variant_id ?? null,
        ]);

        $item->quantity = ($item->quantity ?? 0) + $request->quantity;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart.',
            'data'    => $item->load('product.primaryImage', 'variant'),
        ], 201);
    }

    /** PUT /customer/cart/{id} */
    public function update(Request $request, CartItem $item): JsonResponse
    {
        $this->authorizeItem($item);

        $request->validate(['quantity' => 'required|integer|min:1']);

        $item->update(['quantity' => $request->quantity]);

        return response()->json(['success' => true, 'message' => 'Cart updated.', 'data' => $item]);
    }

    /** DELETE /customer/cart/{id} */
    public function remove(CartItem $item): JsonResponse
    {
        $this->authorizeItem($item);
        $item->delete();

        return response()->json(['success' => true, 'message' => 'Item removed from cart.']);
    }

    /** DELETE /customer/cart */
    public function clear(): JsonResponse
    {
        CartItem::where('user_id', $this->userId())->delete();

        return response()->json(['success' => true, 'message' => 'Cart cleared.']);
    }

    private function authorizeItem(CartItem $item): void
    {
        if ($item->user_id !== $this->userId()) {
            abort(403, 'Access denied.');
        }
    }
}
