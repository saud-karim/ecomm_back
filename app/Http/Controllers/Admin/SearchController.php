<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;

class SearchController extends Controller
{
    /** GET /admin/search?query=... */
    public function index(Request $request): JsonResponse
    {
        $query = $request->input('query');
        
        if (!$query || strlen(trim($query)) < 2) {
            return response()->json([
                'success' => true,
                'data' => [
                    'users'    => [],
                    'products' => [],
                    'orders'   => [],
                ]
            ]);
        }
        
        $term = "%{$query}%";

        // Search Users & Sellers
        $users = User::where(function($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('email', 'like', $term)
                  ->orWhere('phone', 'like', $term);
            })
            ->select('id', 'name', 'email', 'role', 'phone')
            ->limit(5)
            ->get();

        // Search Products
        $products = Product::where(function($q) use ($term) {
                $q->where('name_en', 'like', $term)
                  ->orWhere('name_ar', 'like', $term)
                  ->orWhere('sku', 'like', $term);
            })
            ->select('id', 'name_en', 'name_ar', 'sku', 'price', 'status')
            ->with(['primaryImage:id,url'])
            ->limit(5)
            ->get();

        // Search Orders
        // Allow searching by order ID or total amount
        $orders = Order::where(function($q) use ($query, $term) {
                $q->where('id', 'like', $term)
                  ->orWhere('status', 'like', $term);
                
                // If it's a numeric search, it could be the exact total amount
                if (is_numeric($query)) {
                    $q->orWhere('total', $query);
                }
            })
            ->select('id', 'customer_id', 'total', 'status', 'created_at')
            ->with(['customer:id,name'])
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'users'    => $users,
                'products' => $products,
                'orders'   => $orders,
            ]
        ]);
    }
}
