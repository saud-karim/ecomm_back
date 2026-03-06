<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class CategoryController extends Controller
{
    /** GET /admin/categories */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    /** POST /admin/categories */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_en'    => 'required|string|max:100',
            'name_ar'    => 'nullable|string|max:100',
            'parent_id'  => 'nullable|exists:categories,id',
            'icon'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'sort_order' => 'nullable|integer',
            'is_active'  => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name_en']) . '-' . Str::random(4);
        $validated['is_active'] = $validated['is_active'] ?? true;

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('categories', 'public');
            $validated['icon'] = asset('storage/' . $path);
        }

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created.',
            'data'    => $category->loadCount('products'),
        ], 201);
    }

    /** PUT /admin/categories/{id} */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name_en'    => 'sometimes|string|max:100',
            'name_ar'    => 'nullable|string|max:100',
            'parent_id'  => 'nullable|exists:categories,id',
            'icon'       => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'sort_order' => 'nullable|integer',
            'is_active'  => 'boolean',
        ]);

        if (isset($validated['name_en'])) {
            $validated['slug'] = Str::slug($validated['name_en']) . '-' . Str::random(4);
        }

        if ($request->hasFile('icon')) {
            // Delete old icon if it exists and is local
            if ($category->icon && Str::contains($category->icon, 'storage/categories/')) {
                $oldPath = str_replace(url('storage') . '/', '', $category->icon);
                Storage::disk('public')->delete($oldPath);
            }
            
            $path = $request->file('icon')->store('categories', 'public');
            $validated['icon'] = asset('storage/' . $path);
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated.',
            'data'    => $category->loadCount('products'),
        ]);
    }

    /** DELETE /admin/categories/{id} */
    public function destroy(Category $category): JsonResponse
    {
        // Prevent deleting "Others" fallback category
        if (strtolower($category->name_en) === 'others') {
            return response()->json([
                'success' => false,
                'message' => 'The "Others" category cannot be deleted.',
            ], 422);
        }

        // Reassign any products to "Others" category
        $others = Category::where('name_en', 'Others')->first();
        if ($others) {
            $category->products()->update(['category_id' => $others->id]);
        }

        // Delete icon if it exists and is local
        if ($category->icon && Str::contains($category->icon, 'storage/categories/')) {
            $oldPath = str_replace(url('storage') . '/', '', $category->icon);
            Storage::disk('public')->delete($oldPath);
        }

        $category->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted.']);
    }
}
