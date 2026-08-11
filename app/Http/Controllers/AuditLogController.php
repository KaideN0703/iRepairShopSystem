<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\StockMovement;
use App\Models\NotificationsLog;

use App\Models\Part;
use App\Models\JobOrder;
use App\Models\JobOrderStatusHistory;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('audit.view');

        $module = $request->query('module');
        $action = $request->query('action');
        $search = $request->query('search');

        $logs = AuditLog::with('user')
            ->when($module, function ($q, $module) {
                $q->where('module', $module);
            })
            ->when($action, function ($q, $action) {
                $q->where('action', $action);
            })
            ->when($search, function ($q, $search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

        $stockMovements = StockMovement::with(['part', 'user'])->latest()->take(15)->get();
        $notifications = NotificationsLog::latest()->take(15)->get();

        return view('audit_logs.index', compact('logs', 'stockMovements', 'notifications', 'module', 'action', 'search'));
    }

    /**
     * Task 9 — One-Click Revert action for reversible audit log entries.
     */
    public function revert(Request $request, AuditLog $audit_log)
    {
        $this->authorize('audit.view');

        if (!$audit_log->isReversible()) {
            return back()->with('error', 'This action cannot be reverted (financial, security, or non-reversible entry).');
        }

        if ($audit_log->action === 'stock_adjust') {
            if (preg_match('/SKU\s+([A-Za-z0-9\-\_]+)\s*\(([a-z]+)\s+(\d+)\s+units\)/i', $audit_log->description, $matches)) {
                $sku  = $matches[1];
                $type = strtolower($matches[2]);
                $qty  = (int) $matches[3];

                $part = Part::where('sku', $sku)->first();
                if (!$part) {
                    return back()->with('error', "Cannot revert: Part with SKU '{$sku}' not found.");
                }

                $isAddition = in_array($type, ['addition', 'in', 'restock']);
                if ($isAddition) {
                    if ($part->stock_quantity < $qty) {
                        return back()->with('error', "Cannot revert: Current stock ({$part->stock_quantity}) is lower than revert amount ({$qty}).");
                    }
                    $part->decrement('stock_quantity', $qty);
                    $revertChange = -$qty;
                } else {
                    $part->increment('stock_quantity', $qty);
                    $revertChange = $qty;
                }

                StockMovement::create([
                    'part_id'        => $part->id,
                    'type'           => 'adjustment',
                    'quantity'       => $revertChange,
                    'unit_cost'      => $part->cost_price,
                    'reference_type' => 'AuditRevert',
                    'user_id'        => Auth::id(),
                    'notes'          => "Reverted AuditLog #{$audit_log->id}",
                ]);

                AuditLog::create([
                    'user_id'     => Auth::id(),
                    'user_name'   => Auth::user()?->name,
                    'action'      => 'stock_adjust',
                    'module'      => 'Inventory',
                    'description' => "Reverted AuditLog #{$audit_log->id}: adjusted stock for SKU {$sku} (" . ($isAddition ? "deducted" : "added") . " {$qty} units)",
                    'ip_address'  => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                ]);

                return back()->with('success', "Reverted stock adjustment for SKU {$sku} successfully!");
            } else {
                return back()->with('error', 'Unable to parse stock adjustment details from log description.');
            }
        }

        if ($audit_log->action === 'status_change') {
            if (preg_match('/ticket\s+#?([A-Za-z0-9\-]+)/i', $audit_log->description, $matches)) {
                $ticketNumber = $matches[1];
                $jobOrder     = JobOrder::findByReference($ticketNumber);
                if (!$jobOrder) {
                    return back()->with('error', "Cannot revert: Repair ticket '#{$ticketNumber}' not found.");
                }

                $lastHistory = JobOrderStatusHistory::where('job_order_id', $jobOrder->id)
                    ->where('created_at', '<=', $audit_log->created_at->addSeconds(5))
                    ->latest()
                    ->first();

                $targetStatus = $lastHistory?->status_from;

                if (!$targetStatus || !in_array($targetStatus, JobOrder::STAGES)) {
                    $currentIdx = array_search($jobOrder->status, JobOrder::STAGES);
                    if ($currentIdx !== false && $currentIdx > 0) {
                        $targetStatus = JobOrder::STAGES[$currentIdx - 1];
                    } else {
                        return back()->with('error', 'Cannot revert: No previous stage found for this ticket.');
                    }
                }

                $oldStatus = $jobOrder->status;
                $jobOrder->status = $targetStatus;
                $jobOrder->save();

                JobOrderStatusHistory::create([
                    'job_order_id' => $jobOrder->id,
                    'user_id'      => Auth::id(),
                    'status_from'  => $oldStatus,
                    'status_to'    => $targetStatus,
                    'remarks'      => "Reverted via AuditLog #{$audit_log->id}",
                ]);

                AuditLog::create([
                    'user_id'     => Auth::id(),
                    'user_name'   => Auth::user()?->name,
                    'action'      => 'status_change',
                    'module'      => 'JobOrders',
                    'description' => "Reverted AuditLog #{$audit_log->id}: updated ticket #{$jobOrder->ticket_number} status back to {$targetStatus} from {$oldStatus}",
                    'ip_address'  => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                ]);

                return back()->with('success', "Ticket #{$jobOrder->ticket_number} status reverted to {$targetStatus}!");
            } else {
                return back()->with('error', 'Unable to parse ticket number from status change log description.');
            }
        }

        return back()->with('error', 'Unsupported action type for revert.');
    }
}
