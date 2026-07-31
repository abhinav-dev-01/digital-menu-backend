<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'menu_item_id',
        'customer_name',
        'rating',
        'comment',
        'food_image',
        'reply',
        'status',
    ];

    protected $appends = [
        'author_name',
    ];

    public function getAuthorNameAttribute()
    {
        // Never return Restaurant Owner / Admin name as review author!
        if ($this->relationLoaded('user') && $this->user) {
            $userRole = null;
            if (is_object($this->user->role)) {
                $userRole = strtolower($this->user->role->name ?? '');
            } elseif (is_string($this->user->role)) {
                $userRole = strtolower($this->user->role);
            }

            // Only use user.name if the user is a customer, NOT an admin/owner
            if ($userRole === 'user' || $userRole === 'customer') {
                return $this->user->name;
            }
        }

        if (!empty($this->customer_name)) {
            return $this->customer_name;
        }

        return 'Anonymous';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
