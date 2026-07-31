<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'logo',
        'cover_image',
        'owner_name',
        'contact_number',
        'email',
        'address',
        'google_map_url',
        'working_hours',
        'social_links',
        'about',
        'status',
        'account_status',
        'plan_name',
        'plan_status',
        'trial_starts_at',
        'trial_ends_at',
        'trial_reminder_sent_7',
        'trial_reminder_sent_3',
        'trial_reminder_sent_1',
        'trial_expired_sent',
    ];

    protected $casts = [
        'working_hours' => 'array',
        'social_links' => 'array',
        'trial_starts_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'trial_reminder_sent_7' => 'boolean',
        'trial_reminder_sent_3' => 'boolean',
        'trial_reminder_sent_1' => 'boolean',
        'trial_expired_sent' => 'boolean',
    ];

    protected $appends = [
        'days_remaining',
        'is_trial_expired',
    ];

    public function getDaysRemainingAttribute(): ?int
    {
        if ($this->account_status === 'active' && $this->plan_status === 'active') {
            return null; // Paid active plan
        }

        if (!$this->trial_ends_at) {
            return 0;
        }

        $now = Carbon::now();
        if ($now->greaterThanOrEqualTo($this->trial_ends_at)) {
            return 0;
        }

        $diffInSeconds = $now->diffInSeconds($this->trial_ends_at, false);
        return max(0, (int) ceil($diffInSeconds / 86400));
    }

    public function getIsTrialExpiredAttribute(): bool
    {
        if ($this->account_status === 'expired') {
            return true;
        }

        if ($this->account_status === 'trial' && $this->trial_ends_at) {
            return Carbon::now()->greaterThanOrEqualTo($this->trial_ends_at);
        }

        return false;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class)->orderBy('display_order', 'asc');
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function galleryImages()
    {
        return $this->hasMany(GalleryImage::class);
    }
}
