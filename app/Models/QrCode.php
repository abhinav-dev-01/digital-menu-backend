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

    public function getTargetUrlAttribute($value)
    {
        $configuredFrontendUrl = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/');

        if (!empty($value)) {
            // Replace any stored scheme://domain with the configured frontend_url
            return preg_replace('#^https?://[^/]+#', $configuredFrontendUrl, $value);
        }

        if ($this->restaurant) {
            return "{$configuredFrontendUrl}/menu/" . $this->restaurant->slug;
        }

        return $value;
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
