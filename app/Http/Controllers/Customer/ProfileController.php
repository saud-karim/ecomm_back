<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /** GET /customer/profile */
    public function show(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'avatar'     => $user->avatar,
                'lang'       => $user->lang,
                'created_at' => $user->created_at,
                'stats' => [
                    'orders'   => $user->orders()->count(),
                    'wishlist' => $user->wishlistItems()->count(),
                    'reviews'  => $user->reviews()->count(),
                ],
            ],
        ]);
    }

    /** PUT /customer/profile */
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'name'   => 'sometimes|string|max:150',
            'phone'  => 'sometimes|string|unique:users,phone,'.$user->id,
            'lang'   => 'sometimes|in:ar,en',
        ]);

        $user->update($request->only('name', 'phone', 'lang'));

        return response()->json(['success' => true, 'message' => 'Profile updated.', 'data' => $user]);
    }

    /** PUT /customer/profile/password */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

}
