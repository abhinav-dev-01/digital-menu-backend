<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicalReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'type',
        'title',
        'description',
        'priority',
        'status',
        'super_admin_reply',
        'assigned_to',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
