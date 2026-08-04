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
        $defaultLiveUrl = 'https://digital-menu-frontend-eight.vercel.app';
        $configuredUrl = config('app.frontend_url');

        // Dynamically extract origin from request if calling from live frontend
        $origin = request()->header('Origin') ?? request()->header('Referer');
        if (!empty($origin) && !str_contains($origin, 'localhost') && !str_contains($origin, '127.0.0.1')) {
            $parsed = parse_url($origin);
            if (isset($parsed['scheme']) && isset($parsed['host'])) {
                $port = isset($parsed['port']) ? ":{$parsed['port']}" : '';
                $baseUrl = "{$parsed['scheme']}://{$parsed['host']}{$port}";
            } else {
                $baseUrl = $configuredUrl ?: $defaultLiveUrl;
            }
        } else {
            $baseUrl = $configuredUrl ?: $defaultLiveUrl;
        }

        $baseUrl = rtrim($baseUrl, '/');

        if (!empty($value)) {
            // Replace any scheme://domain (like http://localhost:5173) with the resolved live baseUrl
            return preg_replace('#^https?://[^/]+#', $baseUrl, $value);
        }

        if ($this->restaurant) {
            return "{$baseUrl}/menu/" . $this->restaurant->slug;
        }

        return $value;
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
