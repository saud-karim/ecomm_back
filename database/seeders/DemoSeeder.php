<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Seller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Customers
        $customers = [];
        for ($i = 1; $i <= 10; $i++) {
            $customers[] = User::firstOrCreate(
                ['email' => "customer$i@example.com"],
                [
                    'name'      => "Customer $i",
                    'password'  => Hash::make('password'),
                    'role'      => 'customer',
                    'is_active' => true,
                ]
            );
        }

        // 2. Create Sellers
        $sellers = [];
        $plans = SubscriptionPlan::all();
        
        for ($i = 1; $i <= 5; $i++) {
            $user = User::firstOrCreate(
                ['email' => "seller$i@example.com"],
                [
                    'name'      => "Seller $i",
                    'password'  => Hash::make('password'),
                    'role'      => 'seller',
                    'is_active' => true,
                ]
            );

            $seller = Seller::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'store_name_en'        => "Awesome Store $i",
                    'store_name_ar'        => "متجر رائع $i",
                    'store_slug'           => "awesome-store-$i",
                    'store_description_en' => "The best store for electronics.",
                    'store_description_ar' => "أفضل متجر للإلكترونيات.",
                    'is_approved'          => true,
                ]
            );
            
            $sellers[] = $seller;

            // Subscribe seller to a random plan
            if ($plans->count() > 0) {
                $plan = $plans->random();
                Subscription::create([
                    'seller_id'      => $seller->id,
                    'plan_id'        => $plan->id,
                    'amount_paid'    => $plan->price,
                    'status'         => 'active',
                    'payment_ref'    => 'req_' . Str::random(10),
                    'starts_at'      => now()->subDays(rand(1, 10)),
                    'expires_at'     => now()->addDays(20),
                ]);
            }
        }

        // Add one pending seller
        $pendingUser = User::firstOrCreate(
            ['email' => "pending@example.com"],
            [
                'name'      => "Pending Seller",
                'password'  => Hash::make('password'),
                'role'      => 'seller',
                'is_active' => true,
            ]
        );
        Seller::firstOrCreate(
            ['store_slug' => "pending-store"],
            [
                'user_id'       => $pendingUser->id,
                'store_name_en' => "Pending Store",
                'store_name_ar' => "متجر قيد الانتظار",
                'is_approved'   => false,
            ]
        );

        // 3. Create Products
        $categories = Category::all();
        $products = [];
        
        foreach ($sellers as $seller) {
            for ($p = 1; $p <= 10; $p++) {
                $product = Product::create([
                    'seller_id'            => $seller->id,
                    'category_id'          => $categories->random()->id ?? null,
                    'name_en'              => "Product $p from {$seller->store_name_en}",
                    'name_ar'              => "منتج $p من {$seller->store_name_ar}",
                    'slug'                 => Str::slug("product-$p-{$seller->store_slug}-".Str::random(5)),
                    'description_en'       => "Amazing product description.",
                    'description_ar'       => "وصف منتج مذهل.",
                    'short_description_en' => "Short summary.",
                    'short_description_ar' => "ملخص قصير.",
                    'price'                => rand(50, 500) + 0.99,
                    'sku'                  => "SKU-" . Str::random(8),
                    'quantity'             => rand(10, 100),
                    'is_featured'          => rand(0, 1) == 1,
                    'status'               => 'approved',
                    'is_active'            => true,
                    'views_count'          => rand(10, 500),
                    'created_at'           => now()->subDays(rand(1, 30)),
                ]);
                $products[] = $product;
            }
            
            // Pending product
            Product::create([
                'seller_id'            => $seller->id,
                'category_id'          => $categories->random()->id ?? null,
                'name_en'              => "Pending Product from {$seller->store_name_en}",
                'name_ar'              => "منتج قيد الانتظار من {$seller->store_name_ar}",
                'slug'                 => Str::slug("pending-product-{$seller->store_slug}-".Str::random(5)),
                'price'                => 150.00,
                'status'               => 'pending',
                'is_active'            => false,
            ]);
        }

        // 4. Create Orders
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
        
        for ($o = 1; $o <= 50; $o++) {
            $customer = collect($customers)->random();
            $seller = collect($sellers)->random();
            $sellerProducts = collect($products)->where('seller_id', $seller->id);
            
            if ($sellerProducts->isEmpty()) continue;
            
            $status = collect($statuses)->random();
            $paymentStatus = in_array($status, ['cancelled', 'pending', 'returned']) ? 'unpaid' : 'paid';

            $order = Order::create([
                'customer_id'     => $customer->id,
                'seller_id'       => $seller->id,
                'subtotal'        => 0,
                'tax_amount'      => 10.00,
                'discount_amount' => 0,
                'total'           => 0,
                'status'          => $status,
                'payment_status'  => $paymentStatus,
                'payment_ref'     => 'demo_pay_' . Str::random(5),
                'created_at'      => now()->subDays(rand(1, 60)), // Spread over 2 months
            ]);

            $subtotal = 0;
            // 1 to 3 items per order
            for ($i = 0; $i < rand(1, 3); $i++) {
                $product = $sellerProducts->random();
                $qty = rand(1, 3);
                $unitPrice = $product->price;
                $totalPrice = $qty * $unitPrice;
                
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => $product->name_en,
                    'quantity'     => $qty,
                    'price'        => $unitPrice,
                    'subtotal'     => $totalPrice,
                ]);
                
                $subtotal += $totalPrice;
            }
            
            $order->update([
                'subtotal' => $subtotal,
                'total'    => $subtotal + 10.00,
            ]);
        }
        
        $this->command->info('✅ Demo data seeded successfully (Customers, Sellers, Products, Orders, Subs).');
    }
}
