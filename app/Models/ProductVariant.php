<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'name', 'value', 'price_modifier', 'quantity'];
    protected $casts = ['price_modifier' => 'decimal:2'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
