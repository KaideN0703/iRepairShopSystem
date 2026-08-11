<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairProgressUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'posted_by',
        'pipeline_stage',
        'percentage',
        'description',
        'is_customer_visible',
        'is_rework',
        'rework_reason',
    ];

    protected $casts = [
        'is_customer_visible' => 'boolean',
        'is_rework' => 'boolean',
        'percentage' => 'integer',
    ];

    /** Task 4 — Notes default to internal-only (not customer-visible) */
    protected $attributes = [
        'is_customer_visible' => false,
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function photos()
    {
        return $this->hasMany(RepairProgressPhoto::class);
    }

    public function approvalRequest()
    {
        return $this->hasOne(RepairApprovalRequest::class);
    }
}
