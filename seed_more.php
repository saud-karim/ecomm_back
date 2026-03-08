<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Seller;
use Illuminate\Support\Str;

// ── 1. Categories ───────────────────────────────────────────────
$cats = [
    ['name_en' => 'Electronics',      'name_ar' => 'إلكترونيات',     'image' => 'https://picsum.photos/id/160/120/120'],
    ['name_en' => 'Fashion',          'name_ar' => 'موضة',            'image' => 'https://picsum.photos/id/177/120/120'],
    ['name_en' => 'Home & Kitchen',   'name_ar' => 'منزل ومطبخ',     'image' => 'https://picsum.photos/id/225/120/120'],
    ['name_en' => 'Beauty',           'name_ar' => 'جمال',            'image' => 'https://picsum.photos/id/152/120/120'],
    ['name_en' => 'Sports',           'name_ar' => 'رياضة',           'image' => 'https://picsum.photos/id/21/120/120'],
    ['name_en' => 'Books',            'name_ar' => 'كتب',             'image' => 'https://picsum.photos/id/24/120/120'],
    ['name_en' => 'Toys',             'name_ar' => 'ألعاب',           'image' => 'https://picsum.photos/id/198/120/120'],
    ['name_en' => 'Supermarket',      'name_ar' => 'سوبر ماركت',     'image' => 'https://picsum.photos/id/292/120/120'],
    ['name_en' => 'Mobiles',          'name_ar' => 'موبايلات',        'image' => 'https://picsum.photos/id/201/120/120'],
    ['name_en' => 'Appliances',       'name_ar' => 'أجهزة كهربائية', 'image' => 'https://picsum.photos/id/202/120/120'],
];

$catModels = [];
foreach ($cats as $i => $c) {
    $cat = Category::firstOrCreate(
        ['name_en' => $c['name_en']],
        [
            'name_ar'    => $c['name_ar'],
            'slug'       => Str::slug($c['name_en']),
            'image_url'  => $c['image'],
            'is_active'  => true,
            'sort_order' => $i + 1,
        ]
    );
    $catModels[] = $cat;
}
echo "Categories: " . Category::count() . "\n";

// ── 2. Products ─────────────────────────────────────────────────
$sellers = Seller::where('is_approved', true)->get();
if ($sellers->isEmpty()) {
    echo "No approved sellers. Run DemoSeeder first.\n";
    exit;
}

$products = [
    // Electronics
    ['name_en' => 'Samsung 4K Smart TV 55"',   'name_ar' => 'تلفزيون سامسونج 55 بوصة', 'price' => 12999, 'compare' => 15999, 'cat' => 'Electronics', 'img_id' => 3],
    ['name_en' => 'Apple AirPods Pro',          'name_ar' => 'ايربودز برو من ابل',      'price' => 8999,  'compare' => 10999, 'cat' => 'Electronics', 'img_id' => 160],
    ['name_en' => 'Sony WH-1000XM5 Headphones','name_ar' => 'سماعة سوني لاسلكية',      'price' => 7500,  'compare' => 9000,  'cat' => 'Electronics', 'img_id' => 9],
    ['name_en' => 'Logitech MX Master 3 Mouse','name_ar' => 'ماوس لوجيتك لاسلكي',     'price' => 2200,  'compare' => 2800,  'cat' => 'Electronics', 'img_id' => 48],
    ['name_en' => 'Dell 27" Monitor',          'name_ar' => 'شاشة ديل 27 بوصة',        'price' => 5999,  'compare' => 7500,  'cat' => 'Electronics', 'img_id' => 2],

    // Mobiles
    ['name_en' => 'iPhone 15 Pro Max 256GB',   'name_ar' => 'ايفون 15 برو ماكس',      'price' => 59999, 'compare' => 65000, 'cat' => 'Mobiles', 'img_id' => 27],
    ['name_en' => 'Samsung Galaxy S24 Ultra',  'name_ar' => 'سامسونج جلاكسي S24 الترا','price' => 49999, 'compare' => 55000,'cat' => 'Mobiles', 'img_id' => 16],
    ['name_en' => 'Xiaomi 14 Pro',             'name_ar' => 'شاومي 14 برو',            'price' => 22999, 'compare' => 26000, 'cat' => 'Mobiles', 'img_id' => 336],

    // Fashion
    ['name_en' => 'Levi\'s 501 Original Jeans','name_ar' => 'جينز ليفايز 501',         'price' => 1299,  'compare' => 1799,  'cat' => 'Fashion',  'img_id' => 64],
    ['name_en' => 'Nike Air Max 270 Sneakers', 'name_ar' => 'حذاء نايك اير ماكس',      'price' => 2799,  'compare' => 3500,  'cat' => 'Fashion',  'img_id' => 342],
    ['name_en' => 'Adidas Classic Backpack',   'name_ar' => 'شنطة ظهر اديداس',         'price' => 899,   'compare' => 1200,  'cat' => 'Fashion',  'img_id' => 20],
    ['name_en' => 'Calvin Klein Polo T-Shirt', 'name_ar' => 'تيشيرت كالفن كلاين',      'price' => 499,   'compare' => 699,   'cat' => 'Fashion',  'img_id' => 91],

    // Home & Kitchen
    ['name_en' => 'Instant Pot Pressure Cooker','name_ar' => 'قدر ضغط انستانت بوت',   'price' => 2499,  'compare' => 3200,  'cat' => 'Home & Kitchen', 'img_id' => 42],
    ['name_en' => 'Nespresso Vertuo Coffee Machine','name_ar' => 'ماكينة قهوة نيسبريسو','price' => 4999, 'compare' => 6000,  'cat' => 'Home & Kitchen', 'img_id' => 30],
    ['name_en' => 'Dyson V15 Cordless Vacuum', 'name_ar' => 'مكنسة دايسون لاسلكية',    'price' => 8999,  'compare' => 11000, 'cat' => 'Home & Kitchen', 'img_id' => 116],
    ['name_en' => 'IKEA POÄNG Armchair',        'name_ar' => 'كرسي ايكيا براحة',       'price' => 1599,  'compare' => 1899,  'cat' => 'Home & Kitchen', 'img_id' => 239],

    // Beauty
    ['name_en' => 'L\'Oreal Revitalift Serum',  'name_ar' => 'سيروم لوريال ريفيتاليفت','price' => 599,  'compare' => 799,   'cat' => 'Beauty', 'img_id' => 152],
    ['name_en' => 'Clinique Moisture Surge',     'name_ar' => 'مرطب كلينيك',             'price' => 1299, 'compare' => 1599,  'cat' => 'Beauty', 'img_id' => 26],
    ['name_en' => 'Maison Margiela Replica EDP', 'name_ar' => 'عطر ميزون مارجيلا',      'price' => 4999, 'compare' => 5999,  'cat' => 'Beauty', 'img_id' => 11],

    // Sports
    ['name_en' => 'Garmin Forerunner 265 Watch','name_ar' => 'ساعة جارمن رياضية',      'price' => 9999,  'compare' => 12000, 'cat' => 'Sports', 'img_id' => 50],
    ['name_en' => 'Xiaomi Mi Electric Scooter', 'name_ar' => 'سكوتر شاومي',             'price' => 5499,  'compare' => 6500,  'cat' => 'Sports', 'img_id' => 58],
    ['name_en' => 'Reebok Yoga Mat Pro',         'name_ar' => 'مت يوغا ريبوك',           'price' => 299,   'compare' => 499,   'cat' => 'Sports', 'img_id' => 21],

    // Appliances
    ['name_en' => 'LG French Door Refrigerator','name_ar' => 'ثلاجة LG دبل',           'price' => 22999, 'compare' => 27000, 'cat' => 'Appliances', 'img_id' => 202],
    ['name_en' => 'Samsung 9kg Washing Machine','name_ar' => 'غسالة سامسونج 9 كيلو',   'price' => 14999, 'compare' => 18000, 'cat' => 'Appliances', 'img_id' => 207],
    ['name_en' => 'Philips Air Fryer 7L',        'name_ar' => 'قلاية هوائية فيليبس',   'price' => 3299,  'compare' => 4200,  'cat' => 'Appliances', 'img_id' => 96],

    // Books
    ['name_en' => 'Atomic Habits (Arabic)',      'name_ar' => 'العادات الذرية',          'price' => 129,   'compare' => 159,   'cat' => 'Books', 'img_id' => 24],
    ['name_en' => 'Think and Grow Rich',         'name_ar' => 'فكر وازدد ثروة',         'price' => 99,    'compare' => 139,   'cat' => 'Books', 'img_id' => 25],

    // Supermarket
    ['name_en' => 'Lipton Green Tea 100 Bags',  'name_ar' => 'شاي ليبتون أخضر 100 كيس','price' => 89,   'compare' => 110,   'cat' => 'Supermarket', 'img_id' => 292],
    ['name_en' => 'Olpers Full Cream Milk 1L',  'name_ar' => 'حليب كامل الدسم أولبرز', 'price' => 49,    'compare' => null,  'cat' => 'Supermarket', 'img_id' => 102],

    // Toys
    ['name_en' => 'LEGO Technic 4x4 Off-Roader','name_ar' => 'ليغو تيكنيك',            'price' => 3999,  'compare' => 4999,  'cat' => 'Toys', 'img_id' => 198],
    ['name_en' => 'PlayStation 5 Controller',   'name_ar' => 'ذراع بلاي ستيشن 5',      'price' => 2499,  'compare' => 2999,  'cat' => 'Toys', 'img_id' => 193],
];

$catMap = $catModels;
$seller = $sellers->first();
$created = 0;

foreach ($products as $p) {
    // Find matching category
    $cat = collect($catMap)->firstWhere('name_en', $p['cat']);
    if (!$cat) continue;

    // Skip if already exists
    if (Product::where('name_en', $p['name_en'])->exists()) continue;

    $prod = Product::create([
        'seller_id'            => $sellers->random()->id,
        'category_id'          => $cat->id,
        'name_en'              => $p['name_en'],
        'name_ar'              => $p['name_ar'],
        'slug'                 => Str::slug($p['name_en']).'-'.Str::random(4),
        'description_en'       => "Premium quality {$p['name_en']} with excellent features and great value.",
        'description_ar'       => "{$p['name_ar']} - جودة ممتازة بسعر منافس",
        'short_description_en' => "Top pick in {$p['cat']} category.",
        'short_description_ar' => "الأفضل في فئة {$p['name_ar']}",
        'price'                => $p['price'],
        'compare_price'        => $p['compare'],
        'sku'                  => 'SKU-' . strtoupper(Str::random(8)),
        'quantity'             => rand(20, 200),
        'is_featured'          => rand(0, 3) > 1,
        'status'               => 'approved',
        'is_active'            => true,
        'views_count'          => rand(50, 2000),
    ]);

    ProductImage::create([
        'product_id' => $prod->id,
        'url'        => "https://picsum.photos/id/{$p['img_id']}/400/400",
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    $created++;
}

echo "Categories: " . Category::count() . "\n";
echo "Products added this run: $created\n";
echo "Total approved products: " . Product::approved()->count() . "\n";
