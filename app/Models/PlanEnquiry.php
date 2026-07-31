<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanEnquiry extends Model
{
    use HasFactory;

    protected $table = 'plan_enquiries';

    protected $fillable = [
        'restaurant_name',
        'owner_name',
        'phone',
        'email',
        'city',
        'selected_plan',
        'status',
    ];
}
