<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'slug',
        'image',
        'description',
        'price',
        'discount_price',
        'preparation_time',
        'is_veg',
        'food_type',
        'spicy_level',
        'spice_level',
        'calories',
        'ingredients',
        'allergens',
        'available_start_time',
        'available_end_time',
        'always_available',
        'is_available',
        'is_bestseller',
        'is_recommended',
        'availability_schedule_type',
        'badges',
        'status',
        'sizes',
        'addons',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_veg' => 'boolean',
        'is_available' => 'boolean',
        'always_available' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_recommended' => 'boolean',
        'ingredients' => 'array',
        'allergens' => 'array',
        'badges' => 'array',
        'sizes' => 'array',
        'addons' => 'array',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(MenuImage::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
