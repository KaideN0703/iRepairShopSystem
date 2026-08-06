<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderStatusHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'user_id',
        'status_from',
        'status_to',
        'remarks',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
