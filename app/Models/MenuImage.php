<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuImage extends Model
{
    use HasFactory;

    protected $fillable = ['menu_item_id', 'image_url', 'display_order'];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
