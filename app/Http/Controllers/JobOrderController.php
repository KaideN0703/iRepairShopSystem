<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobOrder;
use App\Models\JobOrderStatusHistory;
use App\Models\JobOrderPart;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Technician;
use App\Models\Part;
use App\Models\StockMovement;
use App\Models\NotificationsLog;
use App\Models\AuditLog;
use App\Models\Attachment;
use App\Models\Warranty;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JobOrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $priority = $request->query('priority');
        $techId = $request->query('technician_id');
        $search = $request->query('search');

        $jobOrders = JobOrder::with(['customer', 'device', 'technician'])
            ->when($status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($priority, function ($q, $priority) {
                $q->where('priority', $priority);
            })
            ->when($techId, function ($q, $techId) {
                $q->where('technician_id', $techId);
            })
            ->when($search, function ($q, $search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('reported_issue', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('device', function ($dq) use ($search) {
                        $dq->where('model', 'like', "%{$search}%")->orWhere('brand', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(15);

        $technicians = Technician::where('is_active', true)->get();
        $stages = JobOrder::STAGES;

        return view('job_orders.index', compact('jobOrders', 'technicians', 'stages', 'status', 'priority', 'techId', 'search'));
    }

    public function create(Request $request)
    {
        $customerId = $request->query('customer_id');
        $customers = Customer::with('devices')->orderBy('name')->get();
        $technicians = Technician::where('is_active', true)->get();
        $parts = Part::where('stock_quantity', '>', 0)->get();

        return view('job_orders.create', compact('customers', 'technicians', 'parts', 'customerId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'device_id' => 'required|exists:devices,id',
            'technician_id' => 'nullable|exists:technicians,id',
            'priority' => 'required|string|in:Low,Normal,High,Urgent',
            'reported_issue' => 'required|string',
            'estimated_completion_date' => 'nullable|date',
            'labor_cost' => 'nullable|numeric|min:0',
            'service_fee' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|string|in:fixed,percentage',
            'discount_value' => 'nullable|numeric|min:0',
            'customer_notes' => 'nullable|string',
            'internal_notes' => 'nullable|string',
        ]);

        $latest = JobOrder::latest('id')->first();
        $nextId = $latest ? $latest->id + 1 : 1;
        $ticketNumber = 'JO-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $validated['ticket_number'] = $ticketNumber;
        $validated['status'] = 'Received';
        $validated['qr_code'] = $ticketNumber;
        $validated['labor_cost'] = $validated['labor_cost'] ?? 0;
        $validated['service_fee'] = $validated['service_fee'] ?? 0;
        $validated['discount_type'] = $validated['discount_type'] ?? 'fixed';
        $validated['discount_value'] = $validated['discount_value'] ?? 0;

        $jobOrder = JobOrder::create($validated);
        $jobOrder->calculateTotalCost();

        // Status history log
        JobOrderStatusHistory::create([
            'job_order_id' => $jobOrder->id,
            'user_id' => Auth::id(),
            'status_from' => null,
            'status_to' => 'Received',
            'remarks' => 'Repair ticket created and device intake completed.',
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'create',
            'module' => 'JobOrders',
            'description' => "Created repair ticket {$ticketNumber}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Send notification log
        NotificationsLog::create([
            'type' => 'SMS',
            'recipient' => $jobOrder->customer?->phone ?? 'N/A',
            'subject' => 'Repair Ticket Received',
            'message' => "Welcome to iRepairShop! Ticket #{$ticketNumber} has been opened for your {$jobOrder->device?->brand} {$jobOrder->device?->model}. Check status online: " . route('status.show', $ticketNumber),
            'status' => 'sent',
            'triggered_by' => 'Job Order Creation',
            'reference_type' => 'JobOrder',
            'reference_id' => $jobOrder->id,
        ]);

        // Update technician workload
        if ($jobOrder->technician_id) {
            $tech = Technician::find($jobOrder->technician_id);
            if ($tech) {
                $tech->increment('active_jobs_count');
            }
        }

        return redirect()->route('job_orders.show', $jobOrder)->with('success', "Ticket {$ticketNumber} created successfully!");
    }

    public function show(JobOrder $jobOrder)
    {
        $jobOrder->load([
            'customer',
            'device',
            'technician',
            'statusHistories.user',
            'parts.part',
            'diagnosis.technician',
            'invoice.payments',
            'warranty',
            'attachments.comments.replies.user',
            'attachments.comments.user',
            'progressUpdates.photos.comments.replies.user',
            'progressUpdates.photos.comments.user',
            'progressUpdates.user',
            'pendingApprovalRequest'
        ]);

        $technicians = Technician::where('is_active', true)->get();
        $availableParts = Part::where('stock_quantity', '>', 0)->get();
        $stages = JobOrder::STAGES;

        return view('job_orders.show', compact('jobOrder', 'technicians', 'availableParts', 'stages'));
    }

    public function updateStatus(Request $request, JobOrder $jobOrder)
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', JobOrder::STAGES),
            'remarks' => 'nullable|string',
        ]);

        $oldStatus = $jobOrder->status;
        $newStatus = $request->status;

        if ($oldStatus === $newStatus) {
            return back()->with('info', 'Status is already set to ' . $newStatus);
        }

        $jobOrder->status = $newStatus;
        if ($newStatus === 'Released' && !$jobOrder->released_at) {
            $jobOrder->released_at = now();

            // Auto create 90-day warranty if not exists
            if (!$jobOrder->warranty) {
                Warranty::create([
                    'job_order_id' => $jobOrder->id,
                    'customer_id' => $jobOrder->customer_id,
                    'device_id' => $jobOrder->device_id,
                    'warranty_period_days' => 90,
                    'start_date' => now(),
                    'end_date' => now()->addDays(90),
                    'coverage_details' => 'Standard 90-Day Parts & Labor Warranty',
                    'status' => 'active',
                ]);
            }

            // Decrement tech active jobs
            if ($jobOrder->technician_id) {
                $tech = Technician::find($jobOrder->technician_id);
                if ($tech && $tech->active_jobs_count > 0) {
                    $tech->decrement('active_jobs_count');
                }
            }
        }

        $jobOrder->save();

        JobOrderStatusHistory::create([
            'job_order_id' => $jobOrder->id,
            'user_id' => Auth::id(),
            'status_from' => $oldStatus,
            'status_to' => $newStatus,
            'remarks' => $request->remarks ?? "Status updated to {$newStatus}",
        ]);

        // Auto trigger customer notification
        NotificationsLog::create([
            'type' => 'SMS',
            'recipient' => $jobOrder->customer?->phone ?? 'N/A',
            'subject' => "Status Alert: {$newStatus}",
            'message' => "iRepairShop Alert: Ticket #{$jobOrder->ticket_number} status updated to [{$newStatus}]." .
                ($newStatus === 'Ready for Pickup' ? ' Your device is ready! Please bring your repair receipt.' : ''),
            'status' => 'sent',
            'triggered_by' => 'Status Change',
            'reference_type' => 'JobOrder',
            'reference_id' => $jobOrder->id,
        ]);

        // Audit Log
        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'status_change',
            'module' => 'JobOrders',
            'description' => "Updated ticket #{$jobOrder->ticket_number} status to {$newStatus}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Ticket status updated to {$newStatus}!");
    }

    public function addPart(Request $request, JobOrder $jobOrder)
    {
        $request->validate([
            'part_id' => 'required|exists:parts,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $part = Part::findOrFail($request->part_id);

        if ($part->stock_quantity < $request->quantity) {
            return back()->with('error', "Insufficient stock for {$part->name}. Available: {$part->stock_quantity}");
        }

        $unitPrice = $part->selling_price;
        $totalPrice = $unitPrice * $request->quantity;

        // Add or update JobOrderPart
        $existingPart = JobOrderPart::where('job_order_id', $jobOrder->id)->where('part_id', $part->id)->first();
        if ($existingPart) {
            $existingPart->quantity += $request->quantity;
            $existingPart->total_price = $existingPart->quantity * $unitPrice;
            $existingPart->save();
        } else {
            JobOrderPart::create([
                'job_order_id' => $jobOrder->id,
                'part_id' => $part->id,
                'quantity' => $request->quantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]);
        }

        // Deduct inventory
        $part->decrement('stock_quantity', $request->quantity);

        // Record stock movement
        StockMovement::create([
            'part_id' => $part->id,
            'type' => 'repair_usage',
            'quantity' => -$request->quantity,
            'unit_cost' => $part->cost_price,
            'reference_type' => 'JobOrder',
            'reference_id' => $jobOrder->id,
            'user_id' => Auth::id(),
            'notes' => "Used on ticket #{$jobOrder->ticket_number}",
        ]);

        // Recalculate cost
        $jobOrder->calculateTotalCost();

        return back()->with('success', "Part '{$part->name}' added to job order and inventory updated.");
    }

    public function removePart(Request $request, JobOrder $jobOrder, JobOrderPart $jobOrderPart)
    {
        $part = Part::find($jobOrderPart->part_id);
        if ($part) {
            // Restore inventory stock
            $part->increment('stock_quantity', $jobOrderPart->quantity);

            StockMovement::create([
                'part_id' => $part->id,
                'type' => 'adjustment',
                'quantity' => $jobOrderPart->quantity,
                'unit_cost' => $part->cost_price,
                'reference_type' => 'JobOrder',
                'reference_id' => $jobOrder->id,
                'user_id' => Auth::id(),
                'notes' => "Removed from ticket #{$jobOrder->ticket_number}",
            ]);
        }

        $jobOrderPart->delete();
        $jobOrder->calculateTotalCost();

        return back()->with('success', 'Part removed from job order and stock restored.');
    }

    public function updateCosts(Request $request, JobOrder $jobOrder)
    {
        $request->validate([
            'labor_cost' => 'required|numeric|min:0',
            'service_fee' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
        ]);

        $jobOrder->labor_cost = $request->labor_cost;
        $jobOrder->service_fee = $request->service_fee;
        $jobOrder->discount_type = $request->discount_type;
        $jobOrder->discount_value = $request->discount_value;
        $jobOrder->calculateTotalCost();

        return back()->with('success', 'Job order costs recalculated successfully.');
    }

    public function assignTechnician(Request $request, JobOrder $jobOrder)
    {
        $request->validate([
            'technician_id' => 'nullable|exists:technicians,id',
        ]);

        $oldTechId = $jobOrder->technician_id;
        $newTechId = $request->technician_id;

        if ($oldTechId != $newTechId) {
            if ($oldTechId) {
                $oldTech = Technician::find($oldTechId);
                if ($oldTech && $oldTech->active_jobs_count > 0) {
                    $oldTech->decrement('active_jobs_count');
                }
            }

            if ($newTechId) {
                $newTech = Technician::find($newTechId);
                if ($newTech) {
                    $newTech->increment('active_jobs_count');
                }
            }

            $jobOrder->technician_id = $newTechId;
            $jobOrder->save();
        }

        return back()->with('success', 'Technician assigned successfully.');
    }

    public function printReceipt(JobOrder $jobOrder)
    {
        $jobOrder->load(['customer', 'device', 'technician', 'parts.part']);
        return view('job_orders.receipt', compact('jobOrder'));
    }

    public function uploadPhoto(Request $request, JobOrder $jobOrder)
    {
        $request->validate([
            'type' => 'required|in:photo_before,photo_after',
            'photo' => 'required|image|max:10240', // 10MB
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('attachments', $filename, 'public');

            Attachment::create([
                'attachable_type' => JobOrder::class,
                'attachable_id' => $jobOrder->id,
                'type' => $request->type,
                'file_path' => '/storage/' . $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);

            return back()->with('success', 'Repair documentation photo uploaded successfully.');
        }

        return back()->with('error', 'Failed to upload photo.');
    }

    public function saveSignature(Request $request, JobOrder $jobOrder)
    {
        $request->validate([
            'signature_data' => 'required|string',
        ]);

        $data = $request->signature_data;
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]);
            $data = base64_decode($data);

            $filename = 'sig_' . $jobOrder->ticket_number . '_' . time() . '.' . $type;
            Storage::disk('public')->put('signatures/' . $filename, $data);

            Attachment::create([
                'attachable_type' => JobOrder::class,
                'attachable_id' => $jobOrder->id,
                'type' => 'customer_signature',
                'file_path' => '/storage/signatures/' . $filename,
                'file_name' => $filename,
                'file_size' => strlen($data),
            ]);

            // If job not released, release it
            if ($jobOrder->status !== 'Released') {
                $jobOrder->status = 'Released';
                $jobOrder->released_at = now();
                $jobOrder->save();
            }

            return back()->with('success', 'Customer signature saved and device released!');
        }

        return back()->with('error', 'Invalid signature image payload.');
    }
}
