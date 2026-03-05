<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /** GET /customer/orders */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('customer_id', auth()->id())
            ->with('seller:id,store_name_en,store_name_ar,store_logo', 'items.product.primaryImage')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json(['success' => true, 'data' => $orders]);
    }

    /** GET /customer/orders/{id} */
    public function show(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        $order->load('seller:id,store_name_en,store_name_ar,store_logo,store_slug', 'address', 'items.product.primaryImage', 'items.variant');

        return response()->json(['success' => true, 'data' => $order]);
    }

    /** POST /customer/orders/{id}/cancel */
    public function cancel(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        if (!in_array($order->status, ['pending', 'processing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled at this stage.',
            ], 422);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Order cancelled successfully.']);
    }

    private function authorizeOrder(Order $order): void
    {
        if ($order->customer_id !== auth()->id()) {
            abort(403, 'Access denied.');
        }
    }
}
