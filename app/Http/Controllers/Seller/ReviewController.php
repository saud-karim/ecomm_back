<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    private function seller()
    {
        return auth()->user()->seller;
    }

    /** GET /seller/reviews */
    public function index(Request $request): JsonResponse
    {
        $seller = $this->seller();

        $reviews = Review::with(['product' => function($query) {
                $query->select('id', 'name_en', 'name_ar', 'sku'); // Adjust fields based on what's available
            }, 'customer' => function($query) {
                $query->select('id', 'name', 'email');
            }])
            ->whereHas('product', function($query) use ($seller) {
                $query->where('seller_id', $seller->id);
            })
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data'    => $reviews,
        ]);
    }
}
