<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number',
        'warranty_id',
        'job_order_id',
        'claim_date',
        'issue_description',
        'resolution_status',
        'resolution_notes',
    ];

    protected $casts = [
        'claim_date' => 'date',
    ];

    public function warranty()
    {
        return $this->belongsTo(Warranty::class);
    }

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }
}
