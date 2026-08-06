<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Models\JobOrder;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class WarrantyController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $warranties = Warranty::with(['jobOrder', 'customer', 'device', 'claims'])
            ->when($status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($search, function ($q, $search) {
                $q->whereHas('customer', function ($cq) use ($search) {
                    $cq->where('name', 'like', "%{$search}%");
                })->orWhereHas('device', function ($dq) use ($search) {
                    $dq->where('model', 'like', "%{$search}%");
                })->orWhereHas('jobOrder', function ($jq) use ($search) {
                    $jq->where('ticket_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('warranties.index', compact('warranties', 'status', 'search'));
    }

    public function show(Warranty $warranty)
    {
        $warranty->load(['jobOrder.parts.part', 'customer', 'device', 'claims']);
        return view('warranties.show', compact('warranty'));
    }

    public function fileClaim(Request $request, Warranty $warranty)
    {
        $request->validate([
            'issue_description' => 'required|string',
        ]);

        $latestClaim = WarrantyClaim::latest('id')->first();
        $nextId = $latestClaim ? $latestClaim->id + 1 : 1;
        $claimNum = 'CLM-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        WarrantyClaim::create([
            'claim_number' => $claimNum,
            'warranty_id' => $warranty->id,
            'job_order_id' => $warranty->job_order_id,
            'claim_date' => now(),
            'issue_description' => $request->issue_description,
            'resolution_status' => 'pending',
        ]);

        $warranty->update(['status' => 'claimed']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'file_warranty_claim',
            'module' => 'Warranties',
            'description' => "Filed warranty claim {$claimNum} for Ticket #{$warranty->jobOrder?->ticket_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('success', "Warranty claim {$claimNum} filed successfully!");
    }

    public function updateClaimStatus(Request $request, WarrantyClaim $claim)
    {
        $request->validate([
            'resolution_status' => 'required|in:pending,approved,rejected,resolved',
            'resolution_notes' => 'nullable|string',
        ]);

        $claim->update([
            'resolution_status' => $request->resolution_status,
            'resolution_notes' => $request->resolution_notes,
        ]);

        return back()->with('success', 'Warranty claim status updated.');
    }
}
