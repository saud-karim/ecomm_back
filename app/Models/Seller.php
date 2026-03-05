<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Translatable;

class Seller extends Model
{
    use HasFactory, Translatable;

    protected array $translatable = ['store_name', 'store_description'];

    protected $fillable = [
        'user_id',
        'store_name_en',
        'store_name_ar',
        'store_slug',
        'store_logo',
        'store_banner',
        'store_description_en',
        'store_description_ar',
        'is_approved',
        'rejection_reason',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
    
    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest();
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();
    }
}
