<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /** GET /admin/users */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->when($request->is_active !== null, fn($q) => $q->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /** GET /admin/users/{id} */
    public function show(User $user): JsonResponse
    {
        $user->load('seller.subscription.plan', 'orders');

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /** PUT /admin/users/{id}/toggle */
    public function toggle(User $user): JsonResponse
    {
        if ($user->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate a super admin.',
            ], 403);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => $user->is_active ? 'User activated.' : 'User deactivated.',
            'data' => ['is_active' => $user->is_active],
        ]);
    }

    /** DELETE /admin/users/{id} */
    public function destroy(User $user): JsonResponse
    {
        if ($user->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a super admin.',
            ], 403);
        }

        // Delete related data in correct order to avoid FK constraint violations.
        // 1. Delete order items linked to this customer's orders, then the orders.
        $orderIds = \DB::table('orders')->where('customer_id', $user->id)->pluck('id');
        if ($orderIds->isNotEmpty()) {
            \DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
            \DB::table('orders')->whereIn('id', $orderIds)->delete();
        }

        // 2. Delete other related records
        \DB::table('cart_items')->where('user_id', $user->id)->delete();
        \DB::table('wishlist_items')->where('user_id', $user->id)->delete();
        \DB::table('reviews')->where('customer_id', $user->id)->delete();
        \DB::table('notifications')->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)->delete();
        \DB::table('addresses')->where('user_id', $user->id)->delete();

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }
}
