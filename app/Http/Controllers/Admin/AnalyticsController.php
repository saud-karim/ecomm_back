<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Seller;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /** GET /admin/dashboard */
    public function dashboard(): JsonResponse
    {
        $totalRevenue  = Order::where('payment_status', 'paid')->sum('total');
        $totalOrders   = Order::count();
        $totalSellers  = Seller::where('is_approved', true)->count();
        $totalCustomers = User::where('role', 'customer')->count();
        $totalProducts = Product::where('status', 'approved')->count();
        $pendingProducts = Product::where('status', 'pending')->count();
        $pendingSellers = Seller::where('is_approved', false)->count();

        // Monthly revenue (last 6 months)
        $monthlyRevenue = Order::selectRaw('
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                SUM(total) as revenue,
                COUNT(*) as orders_count
            ')
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Revenue by status
        $ordersByStatus = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Top selling sellers
        $topSellers = Seller::selectRaw('
                sellers.id,
                sellers.store_name_en as store_name,
                SUM(orders.total) as revenue,
                COUNT(orders.id) as orders
            ')
            ->join('orders', 'sellers.id', '=', 'orders.seller_id')
            ->where('orders.payment_status', 'paid')
            ->groupBy('sellers.id', 'sellers.store_name_en')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // New registrations last 7 days
        $newUsers = User::where('created_at', '>=', now()->subDays(7))->count();
        $newSubscriptions = Subscription::where('created_at', '>=', now()->subDays(7))
            ->where('status', 'active')->count();

        $revenueChart = $monthlyRevenue->map(function ($item) {
            return [
                'date' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                'revenue' => round($item->revenue, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'kpis' => [
                    'total_revenue'    => round($totalRevenue, 2),
                    'total_orders'     => $totalOrders,
                    'total_sellers'    => $totalSellers,
                    'total_customers'  => $totalCustomers,
                    'total_products'   => $totalProducts,
                    'pending_products' => $pendingProducts,
                    'pending_sellers'  => $pendingSellers,
                    'new_users_7d'     => $newUsers,
                    'new_subs_7d'      => $newSubscriptions,
                ],
                'revenue_chart'    => $revenueChart,
                'orders_by_status' => $ordersByStatus,
                'top_sellers'      => $topSellers,
            ],
        ]);
    }

    /** GET /admin/analytics */
    public function analytics(Request $request): JsonResponse
    {
        $from = $request->from_date ?? now()->subDays(30)->toDateString();
        $to   = $request->to_date ?? now()->toDateString();

        $dailyRevenue = Order::selectRaw('
                DATE(created_at) as date,
                SUM(total) as revenue,
                COUNT(*) as orders
            ')
            ->where('payment_status', 'paid')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        $topSellers = Seller::selectRaw('
                sellers.id,
                sellers.store_name_en,
                SUM(orders.total) as orders_sum_total,
                COUNT(orders.id) as orders_count
            ')
            ->join('orders', 'sellers.id', '=', 'orders.seller_id')
            ->where('orders.payment_status', 'paid')
            ->groupBy('sellers.id', 'sellers.store_name_en')
            ->orderByDesc('orders_sum_total')
            ->limit(10)
            ->get();

        $revenueByPlan = Subscription::select('plan_id')
            ->selectRaw('SUM(amount_paid) as total_revenue, COUNT(*) as count')
            ->with('plan:id,name')
            ->where('status', 'active')
            ->groupBy('plan_id')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'period'           => ['from' => $from, 'to' => $to],
                'daily_revenue'    => $dailyRevenue,
                'top_sellers'      => $topSellers,
                'revenue_by_plan'  => $revenueByPlan,
            ],
        ]);
    }

    /** GET /admin/reports */
    public function reports(Request $request): JsonResponse
    {
        $from = $request->from_date ?? now()->startOfMonth()->toDateString();
        $to   = $request->to_date ?? now()->toDateString();

        $salesSummary = Order::selectRaw('
                COUNT(*) as total_orders,
                SUM(total) as gross_revenue,
                SUM(discount_amount) as total_discounts,
                AVG(total) as avg_order_value
            ')
            ->where('payment_status', 'paid')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->first();

        $ordersByStatus = Order::selectRaw('status, COUNT(*) as count')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy('status')
            ->get();

        $newSignups = User::selectRaw('role, COUNT(*) as count')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy('role')
            ->pluck('count', 'role');

        $subscriptionRevenue = Subscription::whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->where('status', 'active')
            ->sum('amount_paid');

        return response()->json([
            'success' => true,
            'data'    => [
                'period'                 => ['from' => $from, 'to' => $to],
                'sales_summary'          => $salesSummary,
                'orders_by_status'       => $ordersByStatus,
                'new_signups'            => $newSignups,
                'subscription_revenue'   => round($subscriptionRevenue, 2),
            ],
        ]);
    }
}
