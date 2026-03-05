<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    private function seller()
    {
        return auth()->user()->seller;
    }

    /** GET /seller/profile */
    public function show(): JsonResponse
    {
        $user   = auth()->user();
        $seller = $user->seller()->with('subscriptions.plan')->first();

        return response()->json([
            'success' => true,
            'data'    => [
                'user'   => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'phone'      => $user->phone,
                    'avatar'     => $user->avatar,
                    'lang'       => $user->lang,
                ],
                'seller' => $seller,
            ],
        ]);
    }

    /** PUT /seller/profile */
    public function update(Request $request): JsonResponse
    {
        $user   = auth()->user();
        $seller = $this->seller();

        $request->validate([
            'name'                   => 'sometimes|string|max:150',
            'phone'                  => 'sometimes|string|unique:users,phone,'.$user->id,
            'lang'                   => 'sometimes|in:ar,en',
            'store_name_en'          => 'sometimes|string|max:100',
            'store_name_ar'          => 'nullable|string|max:100',
            'store_description_en'   => 'nullable|string',
            'store_description_ar'   => 'nullable|string',
        ]);

        $user->update($request->only('name', 'phone', 'lang'));
        $seller->update($request->only('store_name_en', 'store_name_ar', 'store_description_en', 'store_description_ar'));

        return response()->json(['success' => true, 'message' => 'Profile updated.']);
    }

    /** PUT /seller/profile/password */
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
