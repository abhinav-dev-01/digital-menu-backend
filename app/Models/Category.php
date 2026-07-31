<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'parent_id',
        'name',
        'slug',
        'description',
        'image',
        'banner',
        'icon',
        'level',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'display_order' => 'integer',
        'level'         => 'integer',
        'parent_id'     => 'integer',
    ];

    // Relationships
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** Direct children (one level deep) */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('display_order', 'asc');
    }

    /** All descendants nested recursively */
    public function allChildren()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('display_order', 'asc')
            ->with('allChildren');
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }
}
