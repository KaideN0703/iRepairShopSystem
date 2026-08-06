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

    /**
     * Flexible reference lookup for ticket numbers, tokens, invoices, serial numbers, phone numbers, etc.
     */
    public static function findByReference(?string $reference): ?self
    {
        $clean = trim((string) $reference);
        if (empty($clean)) {
            return null;
        }

        // Strip common leading prefix noise: #, Ticket#, Ref#, Invoice#, etc.
        $normalized = preg_replace('/^(ticket|ref|reference|inv|invoice|order|jo)?[\s#:\-]+/i', '', $clean);

        $upperClean = strtoupper($clean);
        $upperNorm = strtoupper($normalized);
        $noSymbols = preg_replace('/[^A-Za-z0-9]/', '', $clean);
        $noSymbolsNorm = preg_replace('/[^A-Za-z0-9]/', '', $normalized);
        $withHyphensNorm = str_replace(' ', '-', $upperNorm);

        $candidates = array_values(array_unique(array_filter([
            $clean,
            $upperClean,
            $upperNorm,
            $withHyphensNorm,
            'JO-' . $upperNorm,
            'JO-' . $withHyphensNorm,
            'INV-' . $upperNorm,
            'INV-' . $withHyphensNorm,
            strtoupper($noSymbols),
            strtoupper($noSymbolsNorm),
            'JO' . strtoupper($noSymbolsNorm),
        ])));

        if (preg_match('/^(\d{4})[\s\-]?(\d{4})$/', $noSymbolsNorm, $m)) {
            $candidates[] = 'JO-' . $m[1] . '-' . $m[2];
            $candidates[] = 'INV-' . $m[1] . '-' . $m[2];
        }

        $digitsOnly = preg_replace('/[^\d]/', '', $clean);

        return self::where(function ($query) use ($candidates, $clean, $upperNorm, $digitsOnly) {
            $query->whereIn(\DB::raw('UPPER(ticket_number)'), array_map('strtoupper', $candidates))
                ->orWhereIn(\DB::raw('REPLACE(UPPER(ticket_number), "-", "")'), array_map('strtoupper', $candidates))
                ->orWhereIn(\DB::raw('REPLACE(UPPER(ticket_number), " ", "")'), array_map('strtoupper', $candidates))
                ->orWhereIn(\DB::raw('UPPER(tracking_token)'), array_map('strtoupper', $candidates))
                ->orWhereIn(\DB::raw('UPPER(qr_code)'), array_map('strtoupper', $candidates))
                ->orWhereHas('invoice', function ($q) use ($candidates) {
                    $q->whereIn(\DB::raw('UPPER(invoice_number)'), array_map('strtoupper', $candidates))
                      ->orWhereIn(\DB::raw('REPLACE(UPPER(invoice_number), "-", "")'), array_map('strtoupper', $candidates));
                })
                ->orWhereHas('device', function ($q) use ($clean, $upperNorm) {
                    $q->where(\DB::raw('UPPER(serial_number)'), $upperNorm)
                      ->orWhere(\DB::raw('UPPER(serial_number)'), 'LIKE', "%{$upperNorm}%");
                })
                ->orWhereHas('customer', function ($q) use ($clean, $upperNorm, $digitsOnly) {
                    $q->where(\DB::raw('UPPER(customer_code)'), $upperNorm)
                      ->orWhere('email', 'LIKE', $clean)
                      ->when(!empty($digitsOnly) && strlen($digitsOnly) >= 4, function ($sq) use ($digitsOnly) {
                          $sq->orWhere(\DB::raw('REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, "+", ""), "-", ""), " ", ""), "(", ""), ")", "")'), 'LIKE', "%{$digitsOnly}%");
                      });
                });
        })->first();
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
