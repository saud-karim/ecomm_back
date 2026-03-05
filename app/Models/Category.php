<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Translatable;

class Category extends Model
{
    use HasFactory, Translatable;

    /**
     * Fields that have _ar and _en variants in the DB.
     * Accessing $category->name will auto-return the locale-correct value.
     */
    protected array $translatable = ['name'];

    protected $fillable = [
        'parent_id',
        'name_en',
        'name_ar',
        'slug',
        'icon',
        'image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
