<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'module',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Task 9 — Check if an audit log action can be safely reverted.
     * Excludes financial/payment/invoice entries.
     */
    public function isReversible(): bool
    {
        // Exclude financial / payment / invoice modules and actions
        if ($this->module === 'Invoices' || in_array($this->action, ['process_payments', 'create_invoice', 'file_warranty_claim'])) {
            return false;
        }

        return in_array($this->action, ['stock_adjust', 'status_change']);
    }
}
