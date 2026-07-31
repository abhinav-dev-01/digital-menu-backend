<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'code_hash',
        'qr_image_url',
        'target_url',
        'total_scans',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'total_scans' => 'integer',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
