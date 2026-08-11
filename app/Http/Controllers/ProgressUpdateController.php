<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobOrder;
use App\Models\RepairProgressUpdate;
use App\Services\ProgressTrackerService;

class ProgressUpdateController extends Controller
{
    public function store(Request $request, JobOrder $jobOrder, ProgressTrackerService $trackerService)
    {
        if (!$jobOrder->exists) {
            $routeParam = $request->route('job_order');
            $jobOrder = $routeParam instanceof JobOrder ? $routeParam : JobOrder::findOrFail($routeParam);
        }

        abort_unless(auth()->user()->canAny(['repairs.manage', 'jobs.manage.full']), 403);
        $this->authorize('manage', $jobOrder); // RepairJobPolicy — technician job scope

        $request->validate([
            'pipeline_stage' => 'required|string|in:' . implode(',', JobOrder::STAGES),
            'percentage' => 'required|integer|min:0|max:100',
            'description' => 'required|string',
            'is_customer_visible' => 'nullable|boolean',
            'rework_reason' => 'nullable|string',
            'photos' => 'required|array|min:1|max:5',
            'photos.*' => 'image|max:10240', // Max 10MB per image
            
            // Optional Approval Request
            'approval_title' => 'nullable|string|max:255',
            'approval_description' => 'nullable|string',
            'additional_cost' => 'nullable|numeric|min:0',
            'additional_time_days' => 'nullable|integer|min:0',
        ]);

        $stage = $request->pipeline_stage;
        $percentage = (int) $request->percentage;
        $description = $request->description;
        $isCustomerVisible = $request->boolean('is_customer_visible', false);
        $reworkReason = $request->rework_reason;
        $photos = $request->file('photos', []);

        $approvalData = null;
        if ($request->filled('approval_title') && $request->filled('approval_description')) {
            $approvalData = [
                'title' => $request->approval_title,
                'description' => $request->approval_description,
                'additional_cost' => $request->additional_cost ?? 0,
                'additional_time_days' => $request->additional_time_days ?? 0,
            ];
        }

        try {
            $update = $trackerService->postUpdate(
                $jobOrder,
                $stage,
                $percentage,
                $description,
                $photos,
                $isCustomerVisible,
                $reworkReason,
                $approvalData
            );

            return back()->with('success', "Progress update ({$percentage}%) posted successfully!");
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', "Failed to post progress update: " . $e->getMessage())->withInput();
        }
    }

    public function index(Request $request, JobOrder $jobOrder)
    {
        if (!$request->wantsJson() && !$request->ajax()) {
            return redirect()->route('job_orders.show', $jobOrder);
        }

        $updates = $jobOrder->progressUpdates()->with(['photos', 'user', 'approvalRequest'])->get();
        return response()->json([
            'current_percentage' => $jobOrder->current_percentage,
            'status' => $jobOrder->status,
            'updates' => $updates,
        ]);
    }
}
