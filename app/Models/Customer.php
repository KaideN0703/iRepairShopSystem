<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'name',
        'email',
        'phone',
        'address',
        'notes',
    ];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function jobOrders()
    {
        return $this->hasMany(JobOrder::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function warranties()
    {
        return $this->hasMany(Warranty::class);
    }
}
