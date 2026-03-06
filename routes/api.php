<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Auth\AuthController;

// Admin
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\SellerController as AdminSellerController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;

// Seller
use App\Http\Controllers\Seller\ProductController as SellerProductController;
use App\Http\Controllers\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Seller\OfferController as SellerOfferController;
use App\Http\Controllers\Seller\CouponController as SellerCouponController;
use App\Http\Controllers\Seller\AnalyticsController as SellerAnalyticsController;
use App\Http\Controllers\Seller\SubscriptionController as SellerSubscriptionController;
use App\Http\Controllers\Seller\ProfileController as SellerProfileController;

// Customer
use App\Http\Controllers\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\WishlistController;
use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;

/*
|─────────────────────────────────────────────────────────────────────────────
| Safqa API Routes  –  All prefixed with /api (via RouteServiceProvider)
|─────────────────────────────────────────────────────────────────────────────
*/

// ══════════════════════════════════════════════════════
// 🌍 PUBLIC ROUTES — No auth required
// ══════════════════════════════════════════════════════

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
    Route::middleware('jwt.auth')->group(function () {
        Route::post('logout',  [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me',       [AuthController::class, 'me']);
    });
});

// Public product browsing
Route::get('products',              [CustomerProductController::class, 'index']);
Route::get('products/{slug}',       [CustomerProductController::class, 'show']);
Route::get('categories',            [CustomerProductController::class, 'categories']);
Route::get('flash-deals',           [CustomerProductController::class, 'flashDeals']);

// ══════════════════════════════════════════════════════
// 👑 SUPER ADMIN ROUTES
// ══════════════════════════════════════════════════════
Route::prefix('admin')
    ->middleware(['jwt.auth', 'role:super_admin'])
    ->group(function () {

    // Dashboard & Reports
    Route::get('dashboard',  [AdminAnalyticsController::class, 'dashboard']);
    Route::get('analytics',  [AdminAnalyticsController::class, 'analytics']);
    Route::get('reports',    [AdminAnalyticsController::class, 'reports']);

    // Users
    Route::get('users',               [AdminUserController::class, 'index']);
    Route::get('users/{user}',        [AdminUserController::class, 'show']);
    Route::put('users/{user}/toggle', [AdminUserController::class, 'toggle']);
    Route::delete('users/{user}',     [AdminUserController::class, 'destroy']);

    // Sellers
    Route::get('sellers',                  [AdminSellerController::class, 'index']);
    Route::get('sellers/{seller}',         [AdminSellerController::class, 'show']);
    Route::put('sellers/{seller}/approve', [AdminSellerController::class, 'approve']);
    Route::put('sellers/{seller}/reject',  [AdminSellerController::class, 'reject']);
    Route::delete('sellers/{seller}',      [AdminSellerController::class, 'destroy']);

    // Products
    Route::get('products',                    [AdminProductController::class, 'index']);
    Route::get('products/{product}',          [AdminProductController::class, 'show']);
    Route::put('products/{product}/approve',  [AdminProductController::class, 'approve']);
    Route::put('products/{product}/reject',   [AdminProductController::class, 'reject']);
    Route::delete('products/{product}',       [AdminProductController::class, 'destroy']);

    // Orders
    Route::get('orders',               [AdminOrderController::class, 'index']);
    Route::get('orders/{order}',       [AdminOrderController::class, 'show']);
    Route::put('orders/{order}/status',[AdminOrderController::class, 'updateStatus']);

    // Plans
    Route::get('plans',          [AdminPlanController::class, 'index']);
    Route::post('plans',         [AdminPlanController::class, 'store']);
    Route::put('plans/{plan}',   [AdminPlanController::class, 'update']);
    Route::delete('plans/{plan}',[AdminPlanController::class, 'destroy']);

    // Subscriptions
    Route::get('subscriptions',                [AdminSubscriptionController::class, 'index']);
    Route::get('subscriptions/{subscription}', [AdminSubscriptionController::class, 'show']);

    // Categories
    Route::get('categories',              [AdminCategoryController::class, 'index']);
    Route::post('categories',             [AdminCategoryController::class, 'store']);
    Route::put('categories/{category}',   [AdminCategoryController::class, 'update']);
    Route::delete('categories/{category}',[AdminCategoryController::class, 'destroy']);
});

// ══════════════════════════════════════════════════════
// 🏪 SELLER ROUTES
// ══════════════════════════════════════════════════════

// Subscription & Profile — accessible before subscription (no 'subscribed' middleware)
Route::prefix('seller')
    ->middleware(['jwt.auth', 'role:seller'])
    ->group(function () {

    // Profile
    Route::get('profile',                 [SellerProfileController::class, 'show']);
    Route::put('profile',                 [SellerProfileController::class, 'update']);
    Route::put('profile/password',        [SellerProfileController::class, 'changePassword']);

    // Subscription
    Route::get('subscription/plans',      [SellerSubscriptionController::class, 'plans']);
    Route::get('subscription/current',    [SellerSubscriptionController::class, 'current']);
    Route::get('subscription/history',    [SellerSubscriptionController::class, 'history']);
    Route::post('subscription/subscribe', [SellerSubscriptionController::class, 'subscribe']);
});

// Seller dashboard — requires active subscription + approval
Route::prefix('seller')
    ->middleware(['jwt.auth', 'role:seller', 'subscribed'])
    ->group(function () {

    // Analytics
    Route::get('analytics/dashboard', [SellerAnalyticsController::class, 'dashboard']);
    Route::get('analytics/revenue',   [SellerAnalyticsController::class, 'revenue']);

    // Products
    Route::get('products',                            [SellerProductController::class, 'index']);
    Route::post('products',                           [SellerProductController::class, 'store']);
    Route::get('products/{product}',                  [SellerProductController::class, 'show']);
    Route::put('products/{product}',                  [SellerProductController::class, 'update']);
    Route::delete('products/{product}',               [SellerProductController::class, 'destroy']);
    Route::post('products/{product}/images',          [SellerProductController::class, 'uploadImages']);
    Route::delete('products/{product}/images/{image}',[SellerProductController::class, 'deleteImage']);

    // Orders
    Route::get('orders',                    [SellerOrderController::class, 'index']);
    Route::get('orders/{order}',            [SellerOrderController::class, 'show']);
    Route::put('orders/{order}/status',     [SellerOrderController::class, 'updateStatus']);

    // Offers
    Route::get('offers',          [SellerOfferController::class, 'index']);
    Route::post('offers',         [SellerOfferController::class, 'store']);
    Route::put('offers/{offer}',  [SellerOfferController::class, 'update']);
    Route::delete('offers/{offer}',[SellerOfferController::class, 'destroy']);

    // Coupons
    Route::get('coupons',            [SellerCouponController::class, 'index']);
    Route::post('coupons',           [SellerCouponController::class, 'store']);
    Route::put('coupons/{coupon}',   [SellerCouponController::class, 'update']);
    Route::delete('coupons/{coupon}',[SellerCouponController::class, 'destroy']);
});

// ══════════════════════════════════════════════════════
// 🛒 CUSTOMER ROUTES
// ══════════════════════════════════════════════════════
Route::prefix('customer')
    ->middleware(['jwt.auth', 'role:customer'])
    ->group(function () {

    // Profile
    Route::get('profile',              [CustomerProfileController::class, 'show']);
    Route::put('profile',              [CustomerProfileController::class, 'update']);
    Route::put('profile/password',     [CustomerProfileController::class, 'changePassword']);

    // Notifications
    Route::get('notifications',         [CustomerProfileController::class, 'notifications']);
    Route::post('notifications/read-all',[CustomerProfileController::class, 'markAllRead']);
    Route::post('notifications/{notification}/read', [CustomerProfileController::class, 'markRead']);

    // Cart
    Route::get('cart',           [CartController::class, 'index']);
    Route::post('cart',          [CartController::class, 'add']);
    Route::put('cart/{item}',    [CartController::class, 'update']);
    Route::delete('cart/{item}', [CartController::class, 'remove']);
    Route::delete('cart',        [CartController::class, 'clear']);

    // Checkout
    Route::post('checkout',                   [CheckoutController::class, 'checkout']);
    Route::post('checkout/validate-coupon',   [CheckoutController::class, 'validateCoupon']);

    // Orders
    Route::get('orders',              [CustomerOrderController::class, 'index']);
    Route::get('orders/{order}',      [CustomerOrderController::class, 'show']);
    Route::post('orders/{order}/cancel', [CustomerOrderController::class, 'cancel']);

    // Reviews
    Route::post('reviews',    [ReviewController::class, 'store']);
    Route::get('reviews',     [ReviewController::class, 'myReviews']);

    // Wishlist
    Route::get('wishlist',           [WishlistController::class, 'index']);
    Route::post('wishlist',          [WishlistController::class, 'toggle']);
    Route::delete('wishlist/{item}', [WishlistController::class, 'remove']);

    // Addresses
    Route::get('addresses',              [AddressController::class, 'index']);
    Route::post('addresses',             [AddressController::class, 'store']);
    Route::put('addresses/{address}',    [AddressController::class, 'update']);
    Route::delete('addresses/{address}', [AddressController::class, 'destroy']);
});

// ══════════════════════════════════════════════════════
// 404 Fallback
// ══════════════════════════════════════════════════════
Route::fallback(fn() => response()->json([
    'success' => false,
    'message' => 'Endpoint not found.',
], 404));
