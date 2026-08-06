<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobOrderPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_order_id',
        'part_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    public function jobOrder()
    {
        return $this->belongsTo(JobOrder::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
