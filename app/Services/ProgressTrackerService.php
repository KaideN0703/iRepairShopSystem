<?php

namespace App\Services;

use App\Models\JobOrder;
use App\Models\RepairProgressUpdate;
use App\Models\RepairProgressPhoto;
use App\Models\RepairApprovalRequest;
use App\Models\JobOrderStatusHistory;
use App\Models\NotificationsLog;
use App\Models\AuditLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class ProgressTrackerService
{
    public function __construct(
        protected ImageCompressionService $imageService
    ) {}

    /**
     * Post a new progress update with photos, rework detection, and optional customer approval request.
     */
    public function postUpdate(
        JobOrder $jobOrder,
        string $stage,
        int $percentage,
        string $description,
        array $photos = [],
        bool $isCustomerVisible = true,
        ?string $reworkReason = null,
        ?array $approvalData = null
    ): RepairProgressUpdate {
        $jobOrder->refresh();

        // Ensure tracking token exists
        if (empty($jobOrder->tracking_token)) {
            $jobOrder->tracking_token = (string) \Illuminate\Support\Str::uuid();
            $jobOrder->save();
        }

        $currentPct = $jobOrder->current_percentage;
        $isRework = $percentage < $currentPct;

        if ($isRework && empty($reworkReason)) {
            throw new \InvalidArgumentException("Percentage drop from {$currentPct}% to {$percentage}% requires a rework reason.");
        }

        // Create Progress Update Record
        $update = RepairProgressUpdate::create([
            'job_order_id' => $jobOrder->id,
            'posted_by' => Auth::id(),
            'pipeline_stage' => $stage,
            'percentage' => $percentage,
            'description' => $description,
            'is_customer_visible' => $isCustomerVisible,
            'is_rework' => $isRework,
            'rework_reason' => $isRework ? $reworkReason : null,
        ]);

        // Process and compress uploaded photos
        foreach ($photos as $photoFile) {
            if ($photoFile instanceof UploadedFile) {
                $paths = $this->imageService->compressAndThumbnail($photoFile, 'progress_photos');
                RepairProgressPhoto::create([
                    'repair_progress_update_id' => $update->id,
                    'file_path' => $paths['file_path'],
                    'thumbnail_path' => $paths['thumbnail_path'],
                ]);
            }
        }

        // Process optional Customer Approval Request
        if (!empty($approvalData) && !empty($approvalData['title']) && !empty($approvalData['description'])) {
            RepairApprovalRequest::create([
                'job_order_id' => $jobOrder->id,
                'repair_progress_update_id' => $update->id,
                'requested_by' => Auth::id(),
                'title' => $approvalData['title'],
                'description' => $approvalData['description'],
                'additional_cost' => $approvalData['additional_cost'] ?? 0,
                'additional_time_days' => $approvalData['additional_time_days'] ?? 0,
                'status' => 'pending',
            ]);

            // Notify Customer about Approval Request
            NotificationsLog::create([
                'type' => 'SMS',
                'recipient' => $jobOrder->customer?->phone ?? 'N/A',
                'subject' => 'Action Required: Repair Approval Needed',
                'message' => "iRepairShop Notice: Approval requested for Ticket #{$jobOrder->ticket_number}: {$approvalData['title']} (+ \${$approvalData['additional_cost']}). Review & approve online: " . route('track.show', $jobOrder->tracking_token),
                'status' => 'sent',
                'triggered_by' => 'Approval Request Created',
                'reference_type' => 'JobOrder',
                'reference_id' => $jobOrder->id,
            ]);
        }

        // Update Denormalized Job Order columns
        $oldStatus = $jobOrder->status;
        $jobOrder->current_percentage = $percentage;
        $jobOrder->status = $stage;

        if ($stage === 'Released' && !$jobOrder->released_at) {
            $jobOrder->released_at = now();
        }

        $jobOrder->save();

        // Audit & History Logs
        if ($oldStatus !== $stage || $isRework) {
            JobOrderStatusHistory::create([
                'job_order_id' => $jobOrder->id,
                'user_id' => Auth::id(),
                'status_from' => $oldStatus,
                'status_to' => $stage,
                'remarks' => $isRework ? "Rework: {$reworkReason} ({$currentPct}% → {$percentage}%)" : "Progress update: {$percentage}%",
            ]);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'progress_update',
            'module' => 'JobOrders',
            'description' => "Posted {$percentage}% progress update on Ticket #{$jobOrder->ticket_number}" . ($isRework ? " (Rework: {$reworkReason})" : ""),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Send SMS/Email ONLY if customer visible
        if ($isCustomerVisible) {
            NotificationsLog::create([
                'type' => 'SMS',
                'recipient' => $jobOrder->customer?->phone ?? 'N/A',
                'subject' => "Progress Update: {$percentage}% Complete",
                'message' => "iRepairShop Update: Ticket #{$jobOrder->ticket_number} is now {$percentage}% complete [{$stage}]. Watch live progress: " . route('track.show', $jobOrder->tracking_token),
                'status' => 'sent',
                'triggered_by' => 'Progress Update',
                'reference_type' => 'JobOrder',
                'reference_id' => $jobOrder->id,
            ]);
        }

        return $update;
    }

    /**
     * Customer responds to an approval request (Approve or Decline).
     */
    public function respondApproval(
        RepairApprovalRequest $request,
        string $responseStatus, // 'approved' or 'declined'
        ?string $note = null
    ): void {
        if ($request->status !== 'pending') {
            throw new \RuntimeException("This approval request has already been {$request->status}.");
        }

        $jobOrder = $request->jobOrder;

        if (in_array($jobOrder->status, ['Completed', 'Released'])) {
            throw new \RuntimeException("Cannot approve changes on a closed/released ticket.");
        }

        $request->status = $responseStatus;
        $request->responded_at = now();
        $request->response_note = $note;
        $request->save();

        if ($responseStatus === 'approved') {
            // Apply additional cost to labor / fee
            if ($request->additional_cost > 0) {
                $jobOrder->service_fee += $request->additional_cost;
                $jobOrder->calculateTotalCost();
            }

            // Extend completion date
            if ($request->additional_time_days > 0) {
                $baseDate = $jobOrder->estimated_completion_date ?? now();
                $jobOrder->estimated_completion_date = $baseDate->addDays($request->additional_time_days);
                $jobOrder->save();
            }
        }

        // Notify Staff / Technician
        NotificationsLog::create([
            'type' => 'Email',
            'recipient' => $request->requestedBy?->email ?? 'staff@irepair.com',
            'subject' => "Customer Responded: Approval Request {$responseStatus}",
            'message' => "Customer {$jobOrder->customer?->name} has {$responseStatus} approval request '{$request->title}' for Ticket #{$jobOrder->ticket_number}." . ($note ? " Note: {$note}" : ""),
            'status' => 'sent',
            'triggered_by' => 'Customer Approval Response',
            'reference_type' => 'JobOrder',
            'reference_id' => $jobOrder->id,
        ]);

        AuditLog::create([
            'user_id' => null,
            'user_name' => $jobOrder->customer?->name ?? 'Customer',
            'action' => 'approval_response',
            'module' => 'JobOrders',
            'description' => "Customer {$responseStatus} request '{$request->title}' for Ticket #{$jobOrder->ticket_number}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Check and expire pending approval requests older than 48 hours.
     */
    public function expireStaleApprovalRequests(): int
    {
        $expiredCount = 0;
        $staleRequests = RepairApprovalRequest::where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(48))
            ->get();

        foreach ($staleRequests as $req) {
            $req->status = 'expired';
            $req->save();
            $expiredCount++;

            NotificationsLog::create([
                'type' => 'Alert',
                'recipient' => 'manager@irepair.com',
                'subject' => "ALERT: Approval Request Expired for Ticket #{$req->jobOrder?->ticket_number}",
                'message' => "Approval request '{$req->title}' for Ticket #{$req->jobOrder?->ticket_number} has sat pending for 48h and is now expired. Please follow up with customer by phone.",
                'status' => 'sent',
                'triggered_by' => 'Approval Expiration Scheduler',
                'reference_type' => 'JobOrder',
                'reference_id' => $req->job_order_id,
            ]);
        }

        return $expiredCount;
    }
}
