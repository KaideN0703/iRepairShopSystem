<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\JobOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->artisan('migrate:fresh');
        $this->artisan('db:seed');
    }

    public function test_photo_upload_on_job_order()
    {
        Storage::fake('public');
        $user = User::where('email', 'admin@irepair.com')->first();
        $jobOrder = JobOrder::first();

        $photo = UploadedFile::fake()->image('test_intake.jpg', 600, 600);

        $response = $this->actingAs($user)->post(route('job_orders.upload_photo', $jobOrder), [
            'type' => 'photo_before',
            'photo' => $photo,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        
        $this->assertCount(1, $jobOrder->attachments);
    }

    public function test_progress_update_photo_upload()
    {
        Storage::fake('public');
        $user = User::where('email', 'admin@irepair.com')->first();
        $jobOrder = JobOrder::first();

        $photo = UploadedFile::fake()->image('milestone.jpg', 600, 600);

        $response = $this->actingAs($user)->post(route('job_orders.progress_updates.store', $jobOrder), [
            'pipeline_stage' => 'Diagnosing',
            'percentage' => 30,
            'description' => 'Tested motherboard power lines.',
            'photos' => [$photo],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
    }
}
