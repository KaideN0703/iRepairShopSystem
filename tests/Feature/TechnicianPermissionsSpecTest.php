<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOrder;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Warranty;
use App\Models\Technician;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TechnicianPermissionsSpecTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_technician_is_denied_job_creation()
    {
        $techUser = User::where('email', 'tech1@irepair.com')->first();
        $customer = Customer::first();
        $device   = Device::first();

        $this->startSession();
        $response = $this->actingAs($techUser)->post('/job_orders', [
            '_token'      => csrf_token(),
            'customer_id' => $customer->id,
            'device_id'   => $device->id,
            'priority'    => 'Normal',
            'reported_issue' => 'Screen cracked',
        ]);

        $response->assertStatus(403);
    }

    public function test_technician_is_denied_device_creation()
    {
        $techUser = User::where('email', 'tech1@irepair.com')->first();
        $customer = Customer::first();

        $this->startSession();
        $response = $this->actingAs($techUser)->post('/devices', [
            '_token'      => csrf_token(),
            'customer_id' => $customer->id,
            'device_type' => 'Smartphone',
            'brand'       => 'Apple',
            'model'       => 'iPhone 13',
        ]);

        $response->assertStatus(403);
    }

    public function test_technician_is_denied_filing_warranty_claims()
    {
        $techUser = User::where('email', 'tech1@irepair.com')->first();
        $warranty = Warranty::first();

        $this->startSession();
        $response = $this->actingAs($techUser)->post(route('warranties.file_claim', $warranty), [
            '_token'            => csrf_token(),
            'issue_description' => 'Battery draining fast again',
        ]);

        $response->assertStatus(403);
    }

    public function test_technician_can_only_view_assigned_job_orders()
    {
        $tech1User = User::where('email', 'tech1@irepair.com')->first();
        $tech1     = $tech1User->technician;

        // Assigned job
        $assignedJob = JobOrder::where('technician_id', $tech1->id)->first();
        $resp1 = $this->actingAs($tech1User)->get(route('job_orders.show', $assignedJob));
        $resp1->assertStatus(200);

        // Unassigned or assigned to someone else
        $tech2User = User::where('email', 'tech2@irepair.com')->first();
        $unassignedJob = JobOrder::where('technician_id', '!=', $tech1->id)->first();

        $resp2 = $this->actingAs($tech1User)->get(route('job_orders.show', $unassignedJob));
        $resp2->assertStatus(403);
    }

    public function test_technician_customer_view_is_scoped_to_assigned_jobs()
    {
        $techUser = User::where('email', 'tech1@irepair.com')->first();
        $response = $this->actingAs($techUser)->get(route('customers.index'));
        $response->assertStatus(200);

        $assignedCustomer = Customer::whereHas('devices.jobOrders', function ($q) use ($techUser) {
            $q->where('technician_id', $techUser->technician?->id);
        })->first();

        if ($assignedCustomer) {
            $response->assertSee($assignedCustomer->name);
        }
    }

    public function test_technician_can_view_read_only_inventory()
    {
        $techUser = User::where('email', 'tech1@irepair.com')->first();

        $response = $this->actingAs($techUser)->get(route('inventory.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Add New Part');
    }

    public function test_technician_can_view_scoped_warranties()
    {
        $techUser = User::where('email', 'tech1@irepair.com')->first();

        $response = $this->actingAs($techUser)->get(route('warranties.index'));
        $response->assertStatus(200);
    }

    public function test_technician_reports_page_is_scoped_to_personal_stats()
    {
        $techUser = User::where('email', 'tech1@irepair.com')->first();

        $response = $this->actingAs($techUser)->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertViewHas('totalRevenue', null);
    }

    public function test_technicians_assignments_endpoint_accessible_to_cashier_and_technician()
    {
        $techUser = User::where('email', 'tech1@irepair.com')->first();
        $cashierUser = User::where('email', 'cashier@irepair.com')->first();

        // 1. Technician access
        $respTech = $this->actingAs($techUser)->get(route('technicians.assignments'));
        $respTech->assertStatus(200);
        $respTech->assertJsonStructure([
            '*' => ['id', 'name', 'current_customer', 'job_status']
        ]);

        // 2. Cashier access
        $respCashier = $this->actingAs($cashierUser)->get(route('technicians.assignments'));
        $respCashier->assertStatus(200);
        $respCashier->assertJsonStructure([
            '*' => ['id', 'name', 'current_customer', 'job_status']
        ]);
    }
}
