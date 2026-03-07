<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Notifications\OrderStatusChangedAdminNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

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

    public function downloadInvoice(Order $order)
    {
        try {
            Log::info("Admin downloading invoice for Order #{$order->id}");
            $order->load(['customer', 'seller.user', 'address', 'items']);
            
            if (!$order->customer) {
                Log::warning("Order #{$order->id} has no customer.");
            }

            $items = $order->items->map(function ($item) {
                return (object) [
                    'product_name' => $item->product_name ?? 'Product',
                    'variant_name' => null,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                ];
            });

            Log::info("Loading PDF view for Order #{$order->id}");
            $pdf = Pdf::loadView('invoice', [
                'order' => $order,
                'items' => $items,
                'isSellerInvoice' => false,
            ]);

            Log::info("Returning PDF stream for Order #{$order->id}");
            return $pdf->download("invoice-{$order->id}.pdf");
        } catch (\Exception $e) {
            Log::error("Invoice download failed: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Invoice generation failed: ' . $e->getMessage()], 500);
        }
    }
}
