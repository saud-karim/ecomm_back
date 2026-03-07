<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Product;
use App\Models\Order;

class SearchController extends Controller
{
    private function seller()
    {
        return auth()->user()->seller;
    }

    /** GET /seller/search?query=... */
    public function index(Request $request): JsonResponse
    {
        $query = $request->input('query');
        
        if (!$query || strlen(trim($query)) < 2) {
            return response()->json([
                'success' => true,
                'data' => [
                    'products' => [],
                    'orders'   => [],
                ]
            ]);
        }
        
        $term = "%{$query}%";
        $sellerId = $this->seller()->id;

        // Search Seller's Products
        $products = Product::where('seller_id', $sellerId)
            ->where(function($q) use ($term) {
                $q->where('name_en', 'like', $term)
                  ->orWhere('name_ar', 'like', $term)
                  ->orWhere('sku', 'like', $term);
            })
            ->select('id', 'name_en', 'name_ar', 'sku', 'price', 'status')
            ->with(['primaryImage:id,url'])
            ->limit(5)
            ->get();

        // Search Seller's Orders
        $orders = collect();
        
        // Find orders containing products from this seller
        $ordersList = Order::whereHas('items', function($q) use ($sellerId) {
                $q->whereHas('product', function($q2) use ($sellerId) {
                    $q2->where('seller_id', $sellerId);
                });
            })
            ->where(function($q) use ($query, $term) {
                $q->where('id', 'like', $term)
                  ->orWhere('status', 'like', $term);
                  
                if (is_numeric($query)) {
                    $q->orWhere('total', $query);
                }
            })
            ->select('id', 'customer_id', 'total', 'status', 'created_at')
            ->with(['customer:id,name'])
            ->limit(5)
            ->get();
            
        $orders = clone $ordersList;
            
        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'orders'   => $orders,
            ]
        ]);
    }
}
