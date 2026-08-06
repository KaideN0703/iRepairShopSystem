<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'supplier_id',
        'sku',
        'barcode',
        'name',
        'description',
        'cost_price',
        'selling_price',
        'stock_quantity',
        'reorder_level',
        'location_rack',
        'compatible_models',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'reorder_level' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(PartCategory::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function jobOrderParts()
    {
        return $this->hasMany(JobOrderPart::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->reorder_level;
    }
}
