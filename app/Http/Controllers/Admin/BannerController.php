<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /** GET /banners (public – active only) */
    public function publicIndex(): JsonResponse
    {
        $banners = Banner::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['success' => true, 'data' => $banners]);
    }

    /** GET /admin/banners */
    public function index(): JsonResponse
    {
        $banners = Banner::orderBy('sort_order')->orderBy('id')->get();

        return response()->json(['success' => true, 'data' => $banners]);
    }

    /** POST /admin/banners */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image'      => 'required|image|max:4096',
            'title_en'   => 'nullable|string|max:200',
            'title_ar'   => 'nullable|string|max:200',
            'subtitle_en'=> 'nullable|string|max:300',
            'subtitle_ar'=> 'nullable|string|max:300',
            'link_url'   => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('banners', 'public');

        $banner = Banner::create([
            'image_path' => $path,
            'title_en'   => $request->title_en,
            'title_ar'   => $request->title_ar,
            'subtitle_en'=> $request->subtitle_en,
            'subtitle_ar'=> $request->subtitle_ar,
            'link_url'   => $request->link_url,
            'sort_order' => $request->sort_order ?? 0,
            'is_active'  => true,
        ]);

        return response()->json(['success' => true, 'data' => $banner], 201);
    }

    /** PUT /admin/banners/{banner} */
    public function update(Request $request, Banner $banner): JsonResponse
    {
        $request->validate([
            'title_en'   => 'nullable|string|max:200',
            'title_ar'   => 'nullable|string|max:200',
            'subtitle_en'=> 'nullable|string|max:300',
            'subtitle_ar'=> 'nullable|string|max:300',
            'link_url'   => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $banner->update($request->only('title_en', 'title_ar', 'subtitle_en', 'subtitle_ar', 'link_url', 'sort_order'));

        // Allow image replacement
        if ($request->hasFile('image')) {
            $request->validate(['image' => 'image|max:4096']);
            Storage::disk('public')->delete($banner->image_path);
            $banner->update(['image_path' => $request->file('image')->store('banners', 'public')]);
        }

        return response()->json(['success' => true, 'data' => $banner->fresh()]);
    }

    /** PUT /admin/banners/{banner}/toggle */
    public function toggle(Banner $banner): JsonResponse
    {
        $banner->update(['is_active' => !$banner->is_active]);

        return response()->json(['success' => true, 'data' => ['is_active' => $banner->is_active]]);
    }

    /** DELETE /admin/banners/{banner} */
    public function destroy(Banner $banner): JsonResponse
    {
        Storage::disk('public')->delete($banner->image_path);
        $banner->delete();

        return response()->json(['success' => true, 'message' => 'Banner deleted.']);
    }
}
