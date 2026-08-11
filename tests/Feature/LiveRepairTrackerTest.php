<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOrder;
use App\Models\RepairProgressUpdate;
use App\Models\RepairApprovalRequest;
use App\Services\ProgressTrackerService;
use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LiveRepairTrackerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_post_progress_update_updates_denormalized_percentage_and_status()
    {
        Storage::fake('public');
        $user = User::where('email', 'tech1@irepair.com')->first();
        $jobOrder = JobOrder::first();
        $initialCount = $jobOrder->progressUpdates()->count();

        $photo = UploadedFile::fake()->image('test_milestone.jpg', 800, 600);

        $response = $this->actingAs($user)->post(route('job_orders.progress_updates.store', $jobOrder), [
            'pipeline_stage' => 'Under Repair',
            'percentage' => 65,
            'description' => 'Replaced battery flex and tested charging current.',
            'is_customer_visible' => 1,
            'photos' => [$photo],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $jobOrder->refresh();
        $this->assertEquals(65, $jobOrder->current_percentage);
        $this->assertEquals('Under Repair', $jobOrder->status);
        $this->assertEquals($initialCount + 1, $jobOrder->progressUpdates()->count());
    }

    public function test_percentage_decrease_requires_rework_reason()
    {
        Storage::fake('public');
        $user = User::where('email', 'tech1@irepair.com')->first();
        $jobOrder = JobOrder::first();
        $jobOrder->current_percentage = 70;
        $jobOrder->save();

        $photo = UploadedFile::fake()->image('rework.jpg', 500, 500);

        // Attempt percentage drop from 70% to 50% without rework reason
        $response = $this->actingAs($user)->post(route('job_orders.progress_updates.store', $jobOrder->id), [
            'pipeline_stage' => 'Under Repair',
            'percentage' => 50,
            'description' => 'Screen drop back to bench',
            'photos' => [$photo],
        ]);

        $response->assertSessionHas('error');

        // Post with rework reason
        $response2 = $this->actingAs($user)->post(route('job_orders.progress_updates.store', $jobOrder->id), [
            'pipeline_stage' => 'Under Repair',
            'percentage' => 50,
            'description' => 'Screen drop back to bench',
            'rework_reason' => 'Part failed quality test',
            'photos' => [$photo],
        ]);

        $response2->assertSessionHasNoErrors();
        $jobOrder->refresh();
        $this->assertEquals(50, $jobOrder->current_percentage);
        
        $latestUpdate = $jobOrder->progressUpdates()->orderBy('id', 'desc')->first();
        $this->assertTrue($latestUpdate->is_rework);
        $this->assertEquals('Part failed quality test', $latestUpdate->rework_reason);
    }

    public function test_customer_invisible_update_not_shown_on_public_page()
    {
        Storage::fake('public');
        $user = User::where('email', 'tech1@irepair.com')->first();
        $jobOrder = JobOrder::first();

        $photo = UploadedFile::fake()->image('internal.jpg', 400, 400);

        // Post internal update
        $this->actingAs($user)->post(route('job_orders.progress_updates.store', $jobOrder), [
            'pipeline_stage' => 'Under Repair',
            'percentage' => 55,
            'description' => 'INTERNAL NOTE: Tech B assisting with micro-soldering.',
            'is_customer_visible' => 0,
            'photos' => [$photo],
        ]);

        $response = $this->get(route('track.show', $jobOrder->tracking_token));
        $response->assertStatus(200);
        $response->assertDontSee('INTERNAL NOTE: Tech B assisting with micro-soldering');
    }

    public function test_customer_approval_request_flow()
    {
        $jobOrder = JobOrder::where('ticket_number', 'JO-2026-0001')->first();
        $approvalRequest = $jobOrder->pendingApprovalRequest;

        $this->assertNotNull($approvalRequest);
        $this->assertEquals('pending', $approvalRequest->status);

        $initialTotal = $jobOrder->total_cost;

        // Customer approves request
        $response = $this->post(route('customer.approval.respond', [$jobOrder->tracking_token, $approvalRequest->id]), [
            'action' => 'approve',
        ]);

        $response->assertSessionHasNoErrors();
        $approvalRequest->refresh();
        $jobOrder->refresh();

        $this->assertEquals('approved', $approvalRequest->status);
        $this->assertGreaterThan($initialTotal, $jobOrder->total_cost);
    }

    public function test_ticket_number_lookup_variations()
    {
        $jobOrder = JobOrder::first();

        // 1. Lowercase ticket number
        $resp1 = $this->post(route('status.lookup'), ['ticket_number' => strtolower($jobOrder->ticket_number)]);
        $resp1->assertRedirect(route('track.show', $jobOrder->tracking_token));

        // 2. Hash prefixed ticket number (e.g. #JO-2026-0001)
        $resp2 = $this->post(route('status.lookup'), ['ticket_number' => '#' . $jobOrder->ticket_number]);
        $resp2->assertRedirect(route('track.show', $jobOrder->tracking_token));

        // 3. Raw number without JO- prefix (e.g. 2026-0001)
        $rawNum = str_replace('JO-', '', $jobOrder->ticket_number);
        $resp3 = $this->post(route('status.lookup'), ['ticket_number' => $rawNum]);
        $resp3->assertRedirect(route('track.show', $jobOrder->tracking_token));

        // 4. Directly accessing /status/{ticket_number} in lowercase
        $resp4 = $this->get(route('status.show', strtolower($jobOrder->ticket_number)));
        $resp4->assertStatus(200);
        $resp4->assertSee($jobOrder->ticket_number);
    }
}
