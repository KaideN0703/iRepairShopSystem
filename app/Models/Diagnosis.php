<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'technician_id',
        'checklist',
        'identified_issues',
        'recommended_repairs',
        'estimated_cost',
        'technician_remarks',
        'ai_suggestions',
    ];

    protected $casts = [
        'checklist' => 'array',
        'ai_suggestions' => 'array',
        'estimated_cost' => 'decimal:2',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
