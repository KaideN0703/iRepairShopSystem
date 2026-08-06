<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'phone',
        'email',
        'device_type',
        'reported_issue',
        'preferred_date',
        'status',
    ];

    protected $casts = [
        'preferred_date' => 'datetime',
    ];
}
