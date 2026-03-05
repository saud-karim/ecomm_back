<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\WishlistItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WishlistController extends Controller
{
    /** GET /customer/wishlist */
    public function index(): JsonResponse
    {
        $items = WishlistItem::where('user_id', auth()->id())
            ->with('product.primaryImage', 'product.seller:id,store_name_en,store_name_ar')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    /** POST /customer/wishlist */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $existing = WishlistItem::where([
            'user_id'    => auth()->id(),
            'product_id' => $request->product_id,
        ])->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['success' => true, 'message' => 'Removed from wishlist.', 'in_wishlist' => false]);
        }

        WishlistItem::create([
            'user_id'    => auth()->id(),
            'product_id' => $request->product_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Added to wishlist.', 'in_wishlist' => true], 201);
    }

    /** DELETE /customer/wishlist/{id} */
    public function remove(WishlistItem $item): JsonResponse
    {
        if ($item->user_id !== auth()->id()) {
            abort(403, 'Access denied.');
        }

        $item->delete();
        return response()->json(['success' => true, 'message' => 'Removed from wishlist.']);
    }
}
