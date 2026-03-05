<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'product_id',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'is_flash_deal',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'is_flash_deal'  => 'boolean',
        'is_active'      => 'boolean',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function isRunning(): bool
    {
        return $this->is_active
            && $this->starts_at->lte(now())
            && $this->ends_at->gte(now());
    }

    public function getDiscountedPriceFor(Product $product): float
    {
        if ($this->discount_type === 'percent') {
            return $product->price * (1 - $this->discount_value / 100);
        }
        return max(0, $product->price - $this->discount_value);
    }
}
