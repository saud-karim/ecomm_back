<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /** GET /admin/products */
    public function index(Request $request): JsonResponse
    {
        $products = Product::with('seller.user', 'category', 'primaryImage')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->seller_id, fn($q) => $q->where('seller_id', $request->seller_id))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->search, fn($q) =>
                $q->where('name_en', 'like', "%{$request->search}%")
                  ->orWhere('name_ar', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }

    /** GET /admin/products/{id} */
    public function show(Product $product): JsonResponse
    {
        $product->load('seller.user', 'category', 'images', 'variants', 'reviews');

        return response()->json([
            'success' => true,
            'data'    => $product,
        ]);
    }

    /** PUT /admin/products/{id}/approve */
    public function approve(Product $product): JsonResponse
    {
        $product->update([
            'status'           => 'approved',
            'is_active'        => true,
            'rejection_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Product '{$product->name}' approved and is now live.",
            'data'    => $product,
        ]);
    }

    /** PUT /admin/products/{id}/reject */
    public function reject(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $product->update([
            'status'           => 'rejected',
            'is_active'        => false,
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Product '{$product->name}' rejected.",
            'data'    => $product,
        ]);
    }

    /** DELETE /admin/products/{id} */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted.',
        ]);
    }
}
