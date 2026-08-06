<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobOrder;
use App\Models\Diagnosis;
use App\Models\Technician;
use App\Models\AuditLog;
use App\Services\AiDiagnosisService;
use Illuminate\Support\Facades\Auth;

class DiagnosisController extends Controller
{
    public function create(JobOrder $jobOrder)
    {
        $jobOrder->load(['device', 'customer', 'technician']);
        $diagnosis = $jobOrder->diagnosis;

        return view('diagnoses.create', compact('jobOrder', 'diagnosis'));
    }

    public function store(Request $request, JobOrder $jobOrder, AiDiagnosisService $aiService)
    {
        $validated = $request->validate([
            'checklist' => 'nullable|array',
            'identified_issues' => 'required|string',
            'recommended_repairs' => 'required|string',
            'estimated_cost' => 'required|numeric|min:0',
            'technician_remarks' => 'nullable|string',
        ]);

        $tech = Auth::user()?->technician ?? $jobOrder->technician;

        // Auto trigger AI Diagnosis if requested or new diagnosis
        $aiResult = $aiService->diagnose(
            $jobOrder->device?->device_type ?? 'Mobile',
            $jobOrder->device?->brand ?? 'Generic',
            $jobOrder->device?->model ?? 'Device',
            $validated['identified_issues']
        );

        $diagnosis = Diagnosis::updateOrCreate(
            ['job_order_id' => $jobOrder->id],
            [
                'technician_id' => $tech?->id,
                'checklist' => $validated['checklist'] ?? [],
                'identified_issues' => $validated['identified_issues'],
                'recommended_repairs' => $validated['recommended_repairs'],
                'estimated_cost' => $validated['estimated_cost'],
                'technician_remarks' => $validated['technician_remarks'],
                'ai_suggestions' => $aiResult,
            ]
        );

        // Update Job Order status to Diagnosing if it was Received
        if ($jobOrder->status === 'Received') {
            $jobOrder->status = 'Diagnosing';
            $jobOrder->save();
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'action' => 'perform_diagnosis',
            'module' => 'Diagnosis',
            'description' => "Saved inspection diagnosis for ticket #{$jobOrder->ticket_number}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('job_orders.show', $jobOrder)->with('success', 'Device inspection & diagnosis saved successfully!');
    }

    public function getAiSuggestions(Request $request, JobOrder $jobOrder, AiDiagnosisService $aiService)
    {
        $reportedIssue = $request->input('reported_issue', $jobOrder->reported_issue);

        $suggestions = $aiService->diagnose(
            $jobOrder->device?->device_type ?? 'Mobile',
            $jobOrder->device?->brand ?? 'Generic',
            $jobOrder->device?->model ?? 'Device',
            $reportedIssue
        );

        return response()->json($suggestions);
    }
}
