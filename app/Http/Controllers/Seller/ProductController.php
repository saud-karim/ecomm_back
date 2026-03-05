<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private function seller()
    {
        return auth()->user()->seller;
    }

    /** GET /seller/products */
    public function index(Request $request): JsonResponse
    {
        $products = $this->seller()->products()
            ->with('category', 'primaryImage')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) =>
                $q->where('name_en', 'like', "%{$request->search}%")
                  ->orWhere('name_ar', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $products]);
    }

    /** GET /seller/products/{id} */
    public function show(Product $product): JsonResponse
    {
        $this->authorizeProduct($product);
        $product->load('category', 'images', 'variants', 'reviews');

        return response()->json(['success' => true, 'data' => $product]);
    }

    /** POST /seller/products */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_en'               => 'required|string|max:200',
            'name_ar'               => 'nullable|string|max:200',
            'category_id'           => 'required|exists:categories,id',
            'description_en'        => 'nullable|string',
            'description_ar'        => 'nullable|string',
            'short_description_en'  => 'nullable|string|max:500',
            'short_description_ar'  => 'nullable|string|max:500',
            'price'                 => 'required|numeric|min:0',
            'compare_price'         => 'nullable|numeric|min:0',
            'sku'                   => 'nullable|string|unique:products,sku',
            'quantity'              => 'required|integer|min:0',
            'is_featured'           => 'boolean',
        ]);

        $product = $this->seller()->products()->create([
            ...$validated,
            'slug'   => Str::slug($validated['name_en']) . '-' . Str::random(5),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product submitted for review.',
            'data'    => $product,
        ], 201);
    }

    /** PUT /seller/products/{id} */
    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($product);

        $validated = $request->validate([
            'name_en'               => 'sometimes|string|max:200',
            'name_ar'               => 'nullable|string|max:200',
            'category_id'           => 'sometimes|exists:categories,id',
            'description_en'        => 'nullable|string',
            'description_ar'        => 'nullable|string',
            'short_description_en'  => 'nullable|string|max:500',
            'short_description_ar'  => 'nullable|string|max:500',
            'price'                 => 'sometimes|numeric|min:0',
            'compare_price'         => 'nullable|numeric|min:0',
            'quantity'              => 'sometimes|integer|min:0',
        ]);

        // Re-submit for review if key fields changed
        if (array_key_exists('price', $validated) || array_key_exists('name_en', $validated)) {
            $validated['status'] = 'pending';
        }

        $product->update($validated);

        return response()->json(['success' => true, 'message' => 'Product updated.', 'data' => $product]);
    }

    /** DELETE /seller/products/{id} */
    public function destroy(Product $product): JsonResponse
    {
        $this->authorizeProduct($product);
        $product->delete();

        return response()->json(['success' => true, 'message' => 'Product deleted.']);
    }

    /** POST /seller/products/{id}/images */
    public function uploadImages(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($product);
        $request->validate([
            'images'            => 'required|array|max:8',
            'images.*'          => 'image|max:5120',
            'primary_index'     => 'nullable|integer',
        ]);

        $uploaded = [];
        foreach ($request->file('images') as $i => $file) {
            $path = $file->store('products', 'public');
            $image = $product->images()->create([
                'image_url'  => $path,
                'is_primary' => $i === ($request->input('primary_index', 0)),
                'sort_order' => $product->images()->count() + $i,
            ]);
            $uploaded[] = $image;
        }

        return response()->json(['success' => true, 'message' => 'Images uploaded.', 'data' => $uploaded], 201);
    }

    /** DELETE /seller/products/{productId}/images/{imageId} */
    public function deleteImage(Product $product, ProductImage $image): JsonResponse
    {
        $this->authorizeProduct($product);

        if ($image->product_id !== $product->id) {
            return response()->json(['success' => false, 'message' => 'Image not found.'], 404);
        }

        $image->delete();
        return response()->json(['success' => true, 'message' => 'Image deleted.']);
    }

    private function authorizeProduct(Product $product): void
    {
        if ($product->seller_id !== $this->seller()->id) {
            abort(403, 'Access denied.');
        }
    }
}
