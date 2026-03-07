<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /** GET /admin/profile */
    public function show(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role,
            ],
        ]);
    }

    /** PUT /admin/profile */
    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();

        $request->validate([
            'name'  => 'sometimes|string|max:150',
            'phone' => 'sometimes|nullable|string|unique:users,phone,' . $user->id,
        ]);

        $user->update($request->only('name', 'phone'));

        return response()->json(['success' => true, 'message' => 'Profile updated successfully.']);
    }

    /** PUT /admin/profile/password */
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
