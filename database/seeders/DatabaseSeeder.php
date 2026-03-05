<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin ──────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@safqa.com'],
            [
                'name'      => 'Safqa Admin',
                'email'     => 'admin@safqa.com',
                'password'  => Hash::make('Admin@12345'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        // ── Subscription Plans ────────────────────────────────────────
        $plans = [
            [
                'name_en'       => 'Basic',
                'name_ar'       => 'الأساسية',
                'slug'          => 'basic',
                'price'         => 99.00,
                'billing_cycle' => 'monthly',
                'max_products'  => 50,
                'max_offers'    => 5,
                'is_featured'   => false,
                'sort_order'    => 1,
                'features'      => [
                    'en' => ['Up to 50 products','Basic analytics','Email support','Standard storefront'],
                    'ar' => ['حتى 50 منتج','تحليلات أساسية','دعم بالبريد الإلكتروني','واجهة متجر قياسية'],
                ],
            ],
            [
                'name_en'       => 'Pro',
                'name_ar'       => 'الاحترافية',
                'slug'          => 'pro',
                'price'         => 249.00,
                'billing_cycle' => 'monthly',
                'max_products'  => 500,
                'max_offers'    => 20,
                'is_featured'   => true,
                'sort_order'    => 2,
                'features'      => [
                    'en' => ['Up to 500 products','Advanced analytics','Flash deals','Priority support','Coupon system'],
                    'ar' => ['حتى 500 منتج','تحليلات متقدمة','عروض فلاش','دعم ذو أولوية','نظام الكوبونات'],
                ],
            ],
            [
                'name_en'       => 'Enterprise',
                'name_ar'       => 'المؤسسية',
                'slug'          => 'enterprise',
                'price'         => 599.00,
                'billing_cycle' => 'monthly',
                'max_products'  => null,
                'max_offers'    => null,
                'is_featured'   => false,
                'sort_order'    => 3,
                'features'      => [
                    'en' => ['Unlimited products','Full reports','Dedicated manager','API access'],
                    'ar' => ['منتجات غير محدودة','تقارير كاملة','مدير مخصص','الوصول إلى API'],
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        // ── Root Categories ───────────────────────────────────────────
        $categories = [
            ['name_en' => 'Electronics',    'name_ar' => 'إلكترونيات',       'slug' => 'electronics',  'icon' => '📱'],
            ['name_en' => 'Fashion',        'name_ar' => 'أزياء',             'slug' => 'fashion',      'icon' => '👗'],
            ['name_en' => 'Home & Garden',  'name_ar' => 'المنزل والحديقة',   'slug' => 'home-garden',  'icon' => '🏠'],
            ['name_en' => 'Sports',         'name_ar' => 'رياضة',             'slug' => 'sports',       'icon' => '⚽'],
            ['name_en' => 'Beauty',         'name_ar' => 'جمال وعناية',       'slug' => 'beauty',       'icon' => '💄'],
            ['name_en' => 'Toys',           'name_ar' => 'ألعاب',             'slug' => 'toys',         'icon' => '🧸'],
            ['name_en' => 'Books',          'name_ar' => 'كتب',               'slug' => 'books',        'icon' => '📚'],
            ['name_en' => 'Automotive',     'name_ar' => 'سيارات',            'slug' => 'automotive',   'icon' => '🚗'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], array_merge($cat, ['is_active' => true]));
        }

        $this->command->info('');
        $this->command->info('✅ Safqa database seeded!');
        $this->command->info('─────────────────────────────');
        $this->command->info('Admin:    admin@safqa.com');
        $this->command->info('Password: Admin@12345');
        $this->command->info('─────────────────────────────');
    }
}
