<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobOrder;
use App\Models\RepairProgressUpdate;

class CustomerPortalController extends Controller
{
    public function index()
    {
        return view('status.index');
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'ticket_number' => 'required|string',
        ]);

        $ticket = trim($request->ticket_number);
        $jobOrder = JobOrder::findByReference($ticket);

        if (!$jobOrder) {
            return back()->with('error', "No repair ticket found matching '{$ticket}'. Please check your receipt, ticket number, or phone number.")->withInput();
        }

        return redirect()->route('track.show', $jobOrder->tracking_token ?? $jobOrder->ticket_number);
    }

    public function show($identifier)
    {
        return $this->renderTrackingPage($identifier);
    }

    public function track($token)
    {
        return $this->renderTrackingPage($token);
    }

    protected function renderTrackingPage(string $token)
    {
        $jobOrder = JobOrder::findByReference($token);

        if (!$jobOrder) {
            return redirect()->route('status.index')->with('error', "No repair ticket found matching '{$token}'. Please enter a valid ticket number or receipt code.");
        }

        $jobOrder->load([
            'customer',
            'device',
            'statusHistories',
            'warranty',
            'customerProgressUpdates.photos.comments.replies.user',
            'customerProgressUpdates.photos.comments.user',
            'customerProgressUpdates.user',
            'pendingApprovalRequest',
            'attachments.comments.replies.user',
            'attachments.comments.user'
        ]);

        $stages = JobOrder::STAGES;
        $currentStageIndex = array_search($jobOrder->status, $stages);
        if ($currentStageIndex === false) {
            $currentStageIndex = 0;
        }

        // Calculate estimated hours remaining based on percentage
        $pct = $jobOrder->current_percentage;
        $remainingHours = max(0.5, round((100 - $pct) * 0.4, 1));

        // Get Before & After Photos for comparison slider if completed
        $beforePhoto = $jobOrder->attachments->where('type', 'photo_before')->first();
        $afterPhoto = $jobOrder->attachments->where('type', 'photo_after')->first();
        
        if (!$beforePhoto && $jobOrder->customerProgressUpdates->isNotEmpty()) {
            $firstUpdate = $jobOrder->customerProgressUpdates->last();
            $beforePhoto = $firstUpdate?->photos->first();
        }
        if (!$afterPhoto && $jobOrder->customerProgressUpdates->isNotEmpty()) {
            $lastUpdate = $jobOrder->customerProgressUpdates->first();
            $afterPhoto = $lastUpdate?->photos->first();
        }

        return view('status.show', compact(
            'jobOrder',
            'stages',
            'currentStageIndex',
            'remainingHours',
            'beforePhoto',
            'afterPhoto'
        ));
    }

    public function progressUpdates(string $token)
    {
        $jobOrder = JobOrder::findByReference($token);

        if (!$jobOrder) {
            return response()->json(['error' => 'Job order not found'], 404);
        }

        return response()->json([
            'current_percentage' => $jobOrder->current_percentage,
            'status' => $jobOrder->status,
        ]);
    }
}
