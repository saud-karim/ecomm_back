<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Notifications\OrderStatusChangedAdminNotification;

class OrderController extends Controller
{
    /** GET /admin/orders */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with('customer:id,name,email', 'seller:id,store_name_en,store_name_ar')
            ->withCount('items')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->seller_id, fn($q) => $q->where('seller_id', $request->seller_id))
            ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->when($request->search, fn($q) => $q->where('id', $request->search)
                ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$request->search}%"))
            )
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    /** GET /admin/orders/{id} */
    public function show(Order $order): JsonResponse
    {
        $order->load('customer', 'seller.user', 'address', 'items.product.primaryImage');

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    /** PUT /admin/orders/{id}/status */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);

        $order->load('customer', 'seller.user');
        $order->update(['status' => $request->status]);

        // Notify the customer
        if ($order->customer) {
            $order->customer->notify(new OrderStatusUpdatedNotification($order));
        }

        // Notify the seller
        if ($order->seller?->user) {
            $order->seller->user->notify(new OrderStatusChangedAdminNotification($order, 'admin'));
        }

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->id} status updated to {$request->status}.",
            'data'    => $order,
        ]);
    }
}
