<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'seller_id',
        'address_id',
        'status',
        'subtotal',
        'discount_amount',
        'coupon_code',
        'tax_amount',
        'total',
        'payment_method',
        'payment_status',
        'payment_ref',
        'notes',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'shipped_at'      => 'datetime',
        'delivered_at'    => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
