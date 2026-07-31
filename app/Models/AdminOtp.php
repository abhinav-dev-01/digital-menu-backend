<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminOtp extends Model
{
    use HasFactory;

    protected $table = 'admin_otps';

    protected $fillable = [
        'user_id',
        'email',
        'otp_hash',
        'expires_at',
        'status',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
