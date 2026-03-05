<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Translatable;

class SubscriptionPlan extends Model
{
    use HasFactory, Translatable;

    protected array $translatable = ['name'];

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'price',
        'billing_cycle',
        'max_products',
        'max_offers',
        'features',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'features'    => 'array',
        'is_active'   => 'boolean',
        'is_featured' => 'boolean',
        'price'       => 'decimal:2',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
