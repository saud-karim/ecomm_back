<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'code',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value'   => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'expires_at'       => 'datetime',
        'is_active'        => 'boolean',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function isValid(): bool
    {
        return $this->is_active
            && ($this->max_uses === null || $this->used_count < $this->max_uses)
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function calculateDiscount(float $orderTotal): float
    {
        if ($this->discount_type === 'percent') {
            return $orderTotal * ($this->discount_value / 100);
        }
        return min($this->discount_value, $orderTotal);
    }
}
