<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'plan_id',
        'status',
        'starts_at',
        'expires_at',
        'payment_ref',
        'amount_paid',
    ];

    protected $casts = [
        'starts_at'  => 'datetime',
        'expires_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }
}
