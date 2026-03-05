<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    private function seller()
    {
        return auth()->user()->seller;
    }

    /** GET /seller/analytics/dashboard */
    public function dashboard(): JsonResponse
    {
        $seller = $this->seller();

        $totalRevenue   = $seller->orders()->where('payment_status', 'paid')->sum('total');
        $totalOrders    = $seller->orders()->count();
        $totalProducts  = $seller->products()->where('status', 'approved')->count();
        $pendingOrders  = $seller->orders()->where('status', 'pending')->count();
        $avgOrderValue  = $seller->orders()->where('payment_status', 'paid')->avg('total') ?? 0;

        // Revenue last 30 days
        $recentRevenue = $seller->orders()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('total');

        // Daily revenue last 7 days
        $dailyRevenue = $seller->orders()
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Orders by status
        $ordersByStatus = $seller->orders()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Top 5 products
        $topProducts = Product::select('products.id', 'products.name_en', 'products.name_ar', 'products.price')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->where('products.seller_id', $seller->id)
            ->selectRaw('SUM(order_items.quantity) as total_sold, SUM(order_items.subtotal) as revenue')
            ->groupBy('products.id', 'products.name_en', 'products.name_ar', 'products.price')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'kpis' => [
                    'total_revenue'    => round($totalRevenue, 2),
                    'recent_revenue'   => round($recentRevenue, 2),
                    'total_orders'     => $totalOrders,
                    'pending_orders'   => $pendingOrders,
                    'total_products'   => $totalProducts,
                    'avg_order_value'  => round($avgOrderValue, 2),
                ],
                'daily_revenue'    => $dailyRevenue,
                'orders_by_status' => $ordersByStatus,
                'top_products'     => $topProducts,
            ],
        ]);
    }

    /** GET /seller/analytics/revenue */
    public function revenue(Request $request): JsonResponse
    {
        $seller = $this->seller();
        $from   = $request->from_date ?? now()->subDays(30)->toDateString();
        $to     = $request->to_date ?? now()->toDateString();

        $revenue = $seller->orders()
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->where('payment_status', 'paid')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $summary = $seller->orders()
            ->selectRaw('COUNT(*) as total_orders, SUM(total) as gross, SUM(discount_amount) as discounts')
            ->where('payment_status', 'paid')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'period'  => ['from' => $from, 'to' => $to],
                'daily'   => $revenue,
                'summary' => $summary,
            ],
        ]);
    }
}
