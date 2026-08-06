<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiDiagnosisService
{
    /**
     * Generate AI-assisted fault diagnosis suggestions based on reported issue and device type.
     */
    public function diagnose(string $deviceType, string $brand, string $model, string $reportedIssue): array
    {
        $apiKey = config('services.anthropic.api_key') ?? env('ANTHROPIC_API_KEY');

        if ($apiKey) {
            try {
                $response = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model' => 'claude-3-5-sonnet-20241022',
                    'max_tokens' => 1000,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => "You are an expert electronics technician AI. Analyze the following device repair case and provide a JSON response with fields: diagnosis (string), confidence (float between 0 and 1), recommended_actions (array of strings), estimated_time_hours (float), suggested_parts (array of strings), estimated_cost (float).\n\nDevice: $brand $model ($deviceType)\nReported Issue: $reportedIssue"
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $content = $response->json('content.0.text');
                    preg_match('/\{.*\}/s', $content, $matches);
                    if (!empty($matches[0])) {
                        $parsed = json_decode($matches[0], true);
                        if (is_array($parsed)) {
                            return $parsed;
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('AI API Call failed, falling back to heuristic engine: ' . $e->getMessage());
            }
        }

        // Smart Fallback Rule Engine
        return $this->heuristicFallback($deviceType, $brand, $model, $reportedIssue);
    }

    private function heuristicFallback(string $deviceType, string $brand, string $model, string $reportedIssue): array
    {
        $issueLower = strtolower($reportedIssue);
        $diagnosis = 'Hardware Fault';
        $actions = ['Perform visual inspection', 'Check motherboard rail voltages', 'Test functionality'];
        $parts = [];
        $time = 1.0;
        $cost = 50.00;
        $confidence = 0.88;

        if (str_contains($issueLower, 'screen') || str_contains($issueLower, 'crack') || str_contains($issueLower, 'glass') || str_contains($issueLower, 'display') || str_contains($issueLower, 'touch')) {
            $diagnosis = 'Display Panel / Digitizer Failure';
            $actions = [
                'Inspect frame for corner impact distortion',
                'Disconnect battery before display cable removal',
                'Transfer TrueTone / proximity sensor flex assembly',
                'Test touch matrix responsiveness across all screen zones',
                'Apply IP68 replacement perimeter adhesive seal'
            ];
            $parts = ["$brand $model Display Panel Assembly"];
            $time = 1.5;
            $cost = 149.00;
            $confidence = 0.95;
        } elseif (str_contains($issueLower, 'battery') || str_contains($issueLower, 'drain') || str_contains($issueLower, 'heat') || str_contains($issueLower, 'shut')) {
            $diagnosis = 'Battery Degradation / Power Management IC Fault';
            $actions = [
                'Measure battery cycle count & health percentage',
                'Check main voltage rail (VCC_MAIN / VBAT) for standby current leak',
                'Replace battery cell with OEM Grade zero-cycle pack',
                'Calibrate charge controller with 0-100% full charge cycle'
            ];
            $parts = ["$brand $model High-Capacity Battery Pack"];
            $time = 1.0;
            $cost = 59.00;
            $confidence = 0.92;
        } elseif (str_contains($issueLower, 'spill') || str_contains($issueLower, 'water') || str_contains($issueLower, 'liquid') || str_contains($issueLower, 'no power')) {
            $diagnosis = 'Liquid Damage & Micro-corrosion on Logic Board';
            $actions = [
                'Disassemble logic board completely and remove heat shields',
                'Run board through 60°C Isopropyl Alcohol Ultrasonic bath',
                'Inspect micro-components under microscope for shorted capacitors',
                'Replace blown power rail caps/resistors and reflow corroded ICs'
            ];
            $parts = ['Micro-soldering Component Kit', 'Thermal Paste / Pad'];
            $time = 2.5;
            $cost = 199.00;
            $confidence = 0.90;
        } elseif (str_contains($issueLower, 'charge') || str_contains($issueLower, 'usb') || str_contains($issueLower, 'port') || str_contains($issueLower, 'plug')) {
            $diagnosis = 'Charging Port Assembly / Flex Ribbon Failure';
            $actions = [
                'Clean lint and debris from charging port cavity',
                'Check pin continuity under microscope',
                'Replace USB-C / Lightning dock flex ribbon board',
                'Test quick-charge protocol voltage (9V / 12V negotiation)'
            ];
            $parts = ["$brand $model Charging Dock Flex Cable"];
            $time = 1.0;
            $cost = 45.00;
            $confidence = 0.94;
        } elseif (str_contains($issueLower, 'ssd') || str_contains($issueLower, 'storage') || str_contains($issueLower, 'slow') || str_contains($issueLower, 'upgrade')) {
            $diagnosis = 'Storage Upgrade / Disk Replacement Request';
            $actions = [
                'Backup user data if requested',
                'Install NVMe High-Speed M.2 SSD Drive',
                'Perform fresh OS Installation & driver optimization',
                'Run read/write speed benchmarking test'
            ];
            $parts = ['1TB PCIe 4.0 NVMe M.2 SSD Drive'];
            $time = 1.5;
            $cost = 119.00;
            $confidence = 0.98;
        }

        return [
            'diagnosis' => $diagnosis,
            'confidence' => $confidence,
            'recommended_actions' => $actions,
            'estimated_time_hours' => $time,
            'suggested_parts' => $parts,
            'estimated_cost' => $cost,
        ];
    }
}
