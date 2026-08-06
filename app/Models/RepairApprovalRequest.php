<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'repair_progress_update_id',
        'requested_by',
        'title',
        'description',
        'additional_cost',
        'additional_time_days',
        'status',
        'responded_at',
        'response_note',
    ];

    protected $casts = [
        'additional_cost' => 'decimal:2',
        'additional_time_days' => 'integer',
        'responded_at' => 'datetime',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function progressUpdate()
    {
        return $this->belongsTo(RepairProgressUpdate::class, 'repair_progress_update_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
