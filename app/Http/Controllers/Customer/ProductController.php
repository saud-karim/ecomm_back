<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /** GET /products  (public - no auth required) */
    public function index(Request $request): JsonResponse
    {
        $products = Product::with('seller:id,store_name_en,store_name_ar,store_slug', 'category:id,name_en,name_ar', 'primaryImage')
            ->approved()
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->seller_id, fn($q) => $q->where('seller_id', $request->seller_id))
            ->when($request->search, fn($q) =>
                $q->where('name_en', 'like', "%{$request->search}%")
                  ->orWhere('name_ar', 'like', "%{$request->search}%")
                  ->orWhere('sku', $request->search)
            )
            ->when($request->min_price, fn($q) => $q->where('price', '>=', $request->min_price))
            ->when($request->max_price, fn($q) => $q->where('price', '<=', $request->max_price))
            ->when($request->featured, fn($q) => $q->where('is_featured', true))
            ->when($request->sort === 'price_asc',  fn($q) => $q->orderBy('price'))
            ->when($request->sort === 'price_desc', fn($q) => $q->orderByDesc('price'))
            ->when($request->sort === 'newest',     fn($q) => $q->latest())
            ->when(!$request->sort, fn($q) => $q->orderByDesc('views_count'))
            ->paginate($request->per_page ?? 20);

        return response()->json(['success' => true, 'data' => $products]);
    }

    /** GET /products/{slug} */
    public function show(string $slug): JsonResponse
    {
        $product = Product::with(
            'seller:id,store_name_en,store_name_ar,store_slug,store_logo',
            'category:id,name_en,name_ar,slug',
            'images',
            'variants',
            'reviews.customer:id,name,avatar',
            'activeOffer'
        )
        ->where('slug', $slug)
        ->approved()
        ->firstOrFail();

        // Increment view count
        $product->increment('views_count');

        return response()->json(['success' => true, 'data' => $product]);
    }

    /** GET /categories (public) */
    public function categories(Request $request): JsonResponse
    {
        $categories = Category::where('is_active', true)
            ->when(!$request->all_levels, fn($q) => $q->whereNull('parent_id'))
            ->with('children')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    /** GET /flash-deals (public) */
    public function flashDeals(): JsonResponse
    {
        $deals = \App\Models\Offer::with('product.primaryImage', 'product.seller:id,store_name_en,store_name_ar')
            ->where('is_flash_deal', true)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('ends_at')
            ->limit(20)
            ->get();

        return response()->json(['success' => true, 'data' => $deals]);
    }
}
