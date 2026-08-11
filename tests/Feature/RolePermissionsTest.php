<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOrder;
use App\Models\RepairApprovalRequest;
use App\Models\RepairProgressUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RolePermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_technician_can_resolve_declined_approval_on_assigned_job()
    {
        $techUser = User::where('email', 'tech1@irepair.com')->first();
        $jobOrder = JobOrder::where('ticket_number', 'JO-2026-0001')->first();
        
        $req = RepairApprovalRequest::create([
            'job_order_id' => $jobOrder->id,
            'requested_by' => $techUser->id,
            'title' => 'Extra repair',
            'description' => 'Test extra repair',
            'additional_cost' => 20,
            'status' => 'declined',
        ]);

        $this->startSession();
        $response = $this->actingAs($techUser)->post(route('job_orders.resolve_declined', [$jobOrder, $req]), [
            '_token' => csrf_token(),
            'resolution' => 'proceed_original',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $req->refresh();
        $this->assertEquals('resolved', $req->status);
    }

    public function test_technician_can_save_signature_on_assigned_job()
    {
        $techUser = User::where('email', 'tech1@irepair.com')->first();
        $jobOrder = JobOrder::where('ticket_number', 'JO-2026-0001')->first();

        $this->startSession();
        $response = $this->actingAs($techUser)->post(route('job_orders.save_signature', $jobOrder), [
            '_token' => csrf_token(),
            'signature_type' => 'typed',
            'typed_signature' => 'John Doe',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $jobOrder->refresh();
        $this->assertEquals('Released', $jobOrder->status);
    }

    public function test_cashier_dashboard_status_counts()
    {
        $cashier = User::where('email', 'cashier@irepair.com')->first();
        $response = $this->actingAs($cashier)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertViewHas('pendingCount');
        $this->assertGreaterThan(0, $response->viewData('pendingCount'));
    }

    public function test_all_roles_can_login_and_access_dashboard()
    {
        $emails = [
            'admin@irepair.com',
            'manager@irepair.com',
            'tech1@irepair.com',
            'tech2@irepair.com',
            'inventory@irepair.com',
            'cashier@irepair.com',
        ];

        foreach ($emails as $email) {
            $this->startSession();
            $loginResp = $this->post('/login', [
                '_token' => csrf_token(),
                'email' => $email,
                'password' => 'password',
            ]);
            $loginResp->assertRedirect(route('dashboard'));

            $user = User::where('email', $email)->first();
            $dashResp = $this->actingAs($user)->get('/dashboard');
            $dashResp->assertStatus(200);
        }
    }
}
