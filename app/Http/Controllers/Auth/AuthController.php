<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    // ─── Register ────────────────────────────────────────────────────────────

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:150',
            'email'         => 'required|email|unique:users',
            'phone'         => 'nullable|string|unique:users|max:20',
            'password'      => 'required|string|min:8|confirmed',
            'role'          => 'sometimes|in:seller,customer',
            'store_name'    => 'required_if:role,seller|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $role = $request->input('role', 'customer');

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => $role,
        ]);

        // If registering as seller, create seller profile
        if ($role === 'seller') {
            Seller::create([
                'user_id'     => $user->id,
                'store_name'  => $request->store_name,
                'store_slug'  => Str::slug($request->store_name) . '-' . Str::random(5),
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data'    => [
                'user'  => $this->userResource($user),
                'token' => $token,
            ],
        ], 201);
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.',
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token. Please try again.',
            ], 500);
        }

        $user = auth()->user();

        if (!$user->is_active) {
            auth()->logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'user'  => $this->userResource($user),
                'token' => $token,
            ],
        ]);
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            // token already invalid
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    // ─── Refresh Token ────────────────────────────────────────────────────────

    public function refresh(): JsonResponse
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'success' => true,
                'data'    => ['token' => $newToken],
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token cannot be refreshed. Please log in again.',
            ], 401);
        }
    }

    // ─── Me ───────────────────────────────────────────────────────────────────

    public function me(): JsonResponse
    {
        $user = auth()->user()->load('seller');

        return response()->json([
            'success' => true,
            'data'    => $this->userResource($user),
        ]);
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    protected function userResource(User $user): array
    {
        $data = [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'role'       => $user->role,
            'avatar'     => $user->avatar,
            'is_active'  => $user->is_active,
            'lang'       => $user->lang,
            'created_at' => $user->created_at,
        ];

        if ($user->relationLoaded('seller') && $user->seller) {
            $data['seller'] = [
                'id'                    => $user->seller->id,
                'store_name'            => $user->seller->store_name,
                'store_slug'            => $user->seller->store_slug,
                'store_logo'            => $user->seller->store_logo,
                'is_approved'           => $user->seller->is_approved,
                'has_active_subscription' => $user->seller->hasActiveSubscription(),
                'subscription_expires_at' => $user->seller->subscription_expires_at,
            ];
        }

        return $data;
    }
}
