<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Notifications\OrderStatusChangedAdminNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\User;

class OrderController extends Controller
{
    private function seller()
    {
        return auth()->user()->seller;
    }

    /** GET /seller/orders */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->seller()->orders()
            ->with('customer:id,name,email,phone', 'items.product.primaryImage')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q) use ($request) {
                $s = $request->search;
                $q->where(function ($sub) use ($s) {
                    $sub->where('id', 'like', "%$s%")
                        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"));
                });
            })
            ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json(['success' => true, 'data' => $orders]);
    }

    /** GET /seller/orders/{id} */
    public function show(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);
        $order->load('customer', 'address', 'items.product.primaryImage', 'items.variant');

        return response()->json(['success' => true, 'data' => $order]);
    }

    /** PUT /seller/orders/{id}/status */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        $request->validate([
            'status' => 'required|in:processing,shipped,delivered,cancelled',
        ]);

        // Seller can only move forward — cannot go from shipped back to processing
        $allowed = match($order->status) {
            'pending'    => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped'    => ['delivered'],
            default      => [],
        };

        if (!in_array($request->status, $allowed)) {
            return response()->json([
                'success' => false,
                'message' => "Cannot update status from '{$order->status}' to '{$request->status}'.",
            ], 422);
        }

        $order->update(['status' => $request->status]);

        // Notify the customer
        $order->customer->notify(new OrderStatusUpdatedNotification($order));

        // Notify the super admin
        $admin = User::where('role', 'super_admin')->first();
        if ($admin) {
            $admin->notify(new OrderStatusChangedAdminNotification($order, 'seller'));
        }

        return response()->json([
            'success' => true,
            'message' => "Order #{$order->id} marked as {$request->status}.",
            'data'    => $order,
        ]);
    }

    private function authorizeOrder(Order $order): void
    {
        if ($order->seller_id !== $this->seller()->id) {
            abort(403, 'Access denied.');
        }
    }

    public function downloadInvoice(Order $order)
    {
        $this->authorizeOrder($order);
        
        $order->load(['customer', 'seller.user', 'address', 'items']);
        
        $items = $order->items->map(function ($item) use ($order) {
            return (object) [
                'product_name' => $item->product_name ?? 'Product',
                'variant_name' => null,
                'status' => $item->status ?? $order->status,
                'price' => $item->price,
                'quantity' => $item->quantity,
            ];
        });

        $pdf = Pdf::loadView('invoice', [
            'order' => $order,
            'items' => $items,
            'isSellerInvoice' => true,
        ]);

        return $pdf->download("invoice-{$order->id}.pdf");
    }
}
