<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'device_id',
        'technician_id',
        'status',
        'current_percentage',
        'tracking_token',
        'priority',
        'reported_issue',
        'estimated_completion_date',
        'labor_cost',
        'parts_cost',
        'service_fee',
        'discount_type',
        'discount_value',
        'total_cost',
        'customer_notes',
        'internal_notes',
        'qr_code',
        'released_at',
    ];

    protected $casts = [
        'estimated_completion_date' => 'date',
        'released_at' => 'datetime',
        'current_percentage' => 'integer',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public const STAGES = [
        'Received',
        'Diagnosing',
        'Waiting for Parts',
        'Under Repair',
        'Testing',
        'Ready for Pickup',
        'Completed',
        'Released',
    ];

    public const STAGE_PERCENTAGE_RANGES = [
        'Received' => [0, 10],
        'Diagnosing' => [10, 25],
        'Waiting for Parts' => [25, 40],
        'Under Repair' => [40, 75],
        'Testing' => [75, 90],
        'Ready for Pickup' => [90, 95],
        'Completed' => [95, 100],
        'Released' => [100, 100],
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->tracking_token)) {
                $model->tracking_token = (string) Str::uuid();
            }
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(JobOrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function parts()
    {
        return $this->hasMany(JobOrderPart::class);
    }

    public function diagnosis()
    {
        return $this->hasOne(Diagnosis::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function warranty()
    {
        return $this->hasOne(Warranty::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function progressUpdates()
    {
        return $this->hasMany(RepairProgressUpdate::class)->latest();
    }

    public function customerProgressUpdates()
    {
        return $this->hasMany(RepairProgressUpdate::class)
            ->where('is_customer_visible', true)
            ->with(['photos', 'user'])
            ->latest();
    }

    public function approvalRequests()
    {
        return $this->hasMany(RepairApprovalRequest::class)->latest();
    }

    public function photoComments()
    {
        return $this->hasMany(PhotoComment::class);
    }

    public function pendingApprovalRequest()
    {
        return $this->hasOne(RepairApprovalRequest::class)
            ->where('status', 'pending')
            ->latest();
    }

    public function calculateTotalCost(): float
    {
        $partsTotal = $this->parts()->sum('total_price');
        $this->parts_cost = $partsTotal;
        $subtotal = $this->labor_cost + $partsTotal + $this->service_fee;

        $discount = 0;
        if ($this->discount_type === 'percentage') {
            $discount = ($subtotal * $this->discount_value) / 100;
        } else {
            $discount = $this->discount_value;
        }

        $total = max(0, $subtotal - $discount);
        $this->total_cost = $total;
        $this->save();

        return $total;
    }
}
