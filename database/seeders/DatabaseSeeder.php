<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\Category;
use App\Models\Seller;
use App\Models\Subscription;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Super Admin
        User::updateOrCreate(
            ['email' => 'admin@safqa.com'],
            ['name' => 'Safqa Admin', 'email' => 'admin@safqa.com', 'password' => Hash::make('Admin@12345'), 'role' => 'super_admin', 'is_active' => true]
        );

        // 2. Plans
        $plansParams = [
            ['name_en' => 'Basic', 'name_ar' => 'الأساسية', 'slug' => 'basic', 'price' => 99.00, 'billing_cycle' => 'monthly', 'max_products' => 50, 'max_offers' => 5, 'is_featured' => false, 'sort_order' => 1, 'features' => ['en' => ['Up to 50 products'], 'ar' => ['حتى 50 منتج']]],
            ['name_en' => 'Pro', 'name_ar' => 'الاحترافية', 'slug' => 'pro', 'price' => 249.00, 'billing_cycle' => 'monthly', 'max_products' => 500, 'max_offers' => 20, 'is_featured' => true, 'sort_order' => 2, 'features' => ['en' => ['Up to 500 products'], 'ar' => ['حتى 500 منتج']]],
            ['name_en' => 'Enterprise', 'name_ar' => 'المؤسسية', 'slug' => 'enterprise', 'price' => 599.00, 'billing_cycle' => 'monthly', 'max_products' => null, 'max_offers' => null, 'is_featured' => false, 'sort_order' => 3, 'features' => ['en' => ['Unlimited products'], 'ar' => ['منتجات غير محدودة']]],
        ];
        
        $planIds = [];
        foreach ($plansParams as $plan) {
            $p = SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
            $planIds[] = $p->id;
        }

        // 3. Categories
        $catsParams = [
            ['name_en' => 'Others',         'name_ar' => 'أخرى',              'slug' => 'others',       'icon' => 'https://images.unsplash.com/photo-1549465220-1a8b9238964d?w=200&h=200&fit=crop', 'sort_order' => 999],
            ['name_en' => 'Electronics',    'name_ar' => 'إلكترونيات',        'slug' => 'electronics',  'icon' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=200&h=200&fit=crop', 'sort_order' => 1],
            ['name_en' => 'Fashion',        'name_ar' => 'أزياء',             'slug' => 'fashion',      'icon' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=200&h=200&fit=crop', 'sort_order' => 2],
            ['name_en' => 'Home & Garden',  'name_ar' => 'المنزل والحديقة',   'slug' => 'home-garden',  'icon' => 'https://images.unsplash.com/photo-1618221195710-dd6b14582f3a?w=200&h=200&fit=crop', 'sort_order' => 3],
            ['name_en' => 'Sports',         'name_ar' => 'رياضة',             'slug' => 'sports',       'icon' => 'https://images.unsplash.com/photo-1517649763962-0c623066013b?w=200&h=200&fit=crop', 'sort_order' => 4],
            ['name_en' => 'Books',          'name_ar' => 'كتب',               'slug' => 'books',        'icon' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=200&h=200&fit=crop', 'sort_order' => 5],
        ];
        $catIds = [];
        foreach ($catsParams as $cat) {
            $c = Category::updateOrCreate(['slug' => $cat['slug']], array_merge($cat, ['is_active' => true]));
            $catIds[] = $c->id;
        }

        // 4. Customers
        $customerIds = [];
        for ($i = 1; $i <= 10; $i++) {
            $u = User::create([
                'name' => "Customer $i", 'email' => "customer$i@example.com", 'password' => Hash::make('password'), 'role' => 'customer', 'is_active' => true
            ]);
            $customerIds[] = $u->id;
        }

        // 5. Sellers & Subscriptions & Products & Orders
        $stores = [
            ['en' => 'Tech World', 'ar' => 'عالم التقنية'],
            ['en' => 'Fashion Hub', 'ar' => 'مركز الأزياء'],
            ['en' => 'Home Comforts', 'ar' => 'راحة المنزل'],
            ['en' => 'Gadget Store', 'ar' => 'متجر الأدوات'],
            ['en' => 'Style Boutique', 'ar' => 'بوتيك الأناقة'],
        ];

        foreach ($stores as $index => $store) {
            $sellerUser = User::create([
                'name' => $store['en'] . ' Owner', 'email' => "seller$index@example.com", 'password' => Hash::make('password'), 'role' => 'seller', 'is_active' => true
            ]);

            $seller = Seller::create([
                'user_id' => $sellerUser->id, 'store_name_en' => $store['en'], 'store_name_ar' => $store['ar'], 'store_slug' => 'store-' . $index, 'is_approved' => true
            ]);

            // Subscription
            $planId = $planIds[array_rand($planIds)];
            $plan = SubscriptionPlan::find($planId);
            Subscription::create([
                'seller_id' => $seller->id, 'plan_id' => $planId, 'status' => 'active', 'amount_paid' => $plan->price, 'starts_at' => now()->subDays(rand(10, 60)), 'expires_at' => now()->addDays(rand(10, 30))
            ]);

            // Products
            $productIds = [];
            for ($p = 1; $p <= 5; $p++) {
                $price = rand(50, 500);
                $prod = Product::create([
                    'seller_id' => $seller->id, 'category_id' => $catIds[array_rand($catIds)], 'name_en' => "Product $p for {$store['en']}", 'name_ar' => "منتج $p لـ {$store['ar']}", 'slug' => "prod-$p-seller-$index", 'price' => $price, 'quantity' => rand(10, 100), 'status' => 'approved', 'is_active' => true
                ]);
                $productIds[] = ['id' => $prod->id, 'price' => $price];
            }

            // Orders (Mock data spreading across past 30 days)
            for ($o = 1; $o <= rand(15, 30); $o++) {
                $customerId = $customerIds[array_rand($customerIds)];
                $prod = $productIds[array_rand($productIds)];
                $qty = rand(1, 3);
                $total = $prod['price'] * $qty;
                
                $orderDate = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23));

                $order = Order::create([
                    'customer_id' => $customerId, 'seller_id' => $seller->id, 'status' => 'delivered', 'payment_status' => 'paid', 'total' => $total, 'subtotal' => $total, 'created_at' => $orderDate, 'updated_at' => $orderDate
                ]);

                OrderItem::create([
                    'order_id' => $order->id, 'product_id' => $prod['id'], 'quantity' => $qty, 'price' => $prod['price'], 'subtotal' => $total, 'product_name' => "Product",
                ]);
            }
        }

        $this->command->info('✅ Safqa database seeded with mock Analytics data!');
    }
}
