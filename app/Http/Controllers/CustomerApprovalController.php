<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobOrder;
use App\Models\RepairApprovalRequest;
use App\Services\ProgressTrackerService;

class CustomerApprovalController extends Controller
{
    public function respond(
        Request $request,
        $token,
        RepairApprovalRequest $approvalRequest,
        ProgressTrackerService $trackerService
    ) {
        if (!$approvalRequest->exists) {
            $routeParam = $request->route('approval_request');
            $approvalRequest = $routeParam instanceof RepairApprovalRequest ? $routeParam : RepairApprovalRequest::findOrFail($routeParam);
        }

        $jobOrder = JobOrder::findByReference($token);

        if (!$jobOrder) {
            abort(404, 'Invalid tracking token or ticket number.');
        }

        if ($approvalRequest->job_order_id !== $jobOrder->id) {
            abort(404, 'Invalid approval request for this ticket.');
        }

        $action = $request->input('action'); // 'approve' or 'decline'
        $note = $request->input('response_note');

        if (!in_array($action, ['approve', 'decline'])) {
            return back()->with('error', 'Invalid approval action.');
        }

        $status = $action === 'approve' ? 'approved' : 'declined';

        try {
            $trackerService->respondApproval($approvalRequest, $status, $note);

            $msg = $status === 'approved' 
                ? 'Thank you! You have approved the additional repair request. Work will proceed immediately.'
                : 'Your decision has been logged. Our repair team has been notified.';

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
