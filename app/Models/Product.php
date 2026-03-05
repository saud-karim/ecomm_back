<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Translatable;

class Product extends Model
{
    use HasFactory, Translatable;

    protected array $translatable = ['name', 'description', 'short_description'];

    protected $fillable = [
        'seller_id',
        'category_id',
        'name_en',
        'name_ar',
        'slug',
        'description_en',
        'description_ar',
        'short_description_en',
        'short_description_ar',
        'price',
        'compare_price',
        'sku',
        'quantity',
        'is_featured',
        'is_active',
        'status',
        'rejection_reason',
        'views_count',
    ];

    protected $casts = [
        'is_featured'   => 'boolean',
        'is_active'     => 'boolean',
        'price'         => 'decimal:2',
        'compare_price' => 'decimal:2',
    ];

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved')->where('is_active', true);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->approved();
    }

    // Computed
    public function getDiscountPercentAttribute(): int
    {
        if ($this->compare_price && $this->compare_price > $this->price) {
            return (int) round(100 - ($this->price / $this->compare_price * 100));
        }
        return 0;
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    // Relationships
    public function seller()       { return $this->belongsTo(Seller::class); }
    public function category()     { return $this->belongsTo(Category::class); }
    public function images()       { return $this->hasMany(ProductImage::class)->orderBy('sort_order'); }
    public function primaryImage() { return $this->hasOne(ProductImage::class)->where('is_primary', true); }
    public function variants()     { return $this->hasMany(ProductVariant::class); }
    public function offers()       { return $this->hasMany(Offer::class)->where('is_active', true); }
    public function activeOffer()
    {
        return $this->hasOne(Offer::class)
            ->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->latest();
    }
    public function reviews()      { return $this->hasMany(Review::class)->where('is_approved', true); }
    public function orderItems()   { return $this->hasMany(OrderItem::class); }
    public function wishlistItems(){ return $this->hasMany(WishlistItem::class); }
}
