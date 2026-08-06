<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOrder;
use App\Models\Customer;
use App\Models\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RepairShopSystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed');
    }
    public function test_public_customer_portal_status_lookup()
    {
        $response = $this->get('/status');
        $response->assertStatus(200);

        $response = $this->get('/status/JO-2026-0001');
        $response->assertStatus(200);
    }

    public function test_authenticated_dashboard_access()
    {
        $user = User::where('email', 'admin@irepair.com')->first();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_job_orders_index_access()
    {
        $user = User::where('email', 'admin@irepair.com')->first();
        $response = $this->actingAs($user)->get('/job_orders');
        $response->assertStatus(200);
    }

    public function test_inventory_parts_access()
    {
        $user = User::where('email', 'admin@irepair.com')->first();
        $response = $this->actingAs($user)->get('/inventory');
        $response->assertStatus(200);
    }

    public function test_reports_access()
    {
        $user = User::where('email', 'admin@irepair.com')->first();
        $response = $this->actingAs($user)->get('/reports');
        $response->assertStatus(200);
    }
}
