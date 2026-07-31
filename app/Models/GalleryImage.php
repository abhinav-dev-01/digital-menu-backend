<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'title',
        'album_name',
        'image_url',
        'type',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
