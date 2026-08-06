<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add current_percentage and tracking_token to job_orders
        Schema::table('job_orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_percentage')->default(0)->after('status');
            $table->string('tracking_token')->nullable()->unique()->after('qr_code');
        });

        // 2. Repair Progress Updates
        Schema::create('repair_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained('job_orders')->onDelete('cascade');
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('pipeline_stage');
            $table->unsignedTinyInteger('percentage'); // 0-100
            $table->text('description');
            $table->boolean('is_customer_visible')->default(true);
            $table->boolean('is_rework')->default(false);
            $table->text('rework_reason')->nullable();
            $table->timestamps();
        });

        // 3. Repair Progress Photos
        Schema::create('repair_progress_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_progress_update_id')->constrained('repair_progress_updates')->onDelete('cascade');
            $table->string('file_path');
            $table->string('thumbnail_path');
            $table->timestamps();
        });

        // 4. Repair Approval Requests (Customer Approval Module)
        Schema::create('repair_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained('job_orders')->onDelete('cascade');
            $table->foreignId('repair_progress_update_id')->nullable()->constrained('repair_progress_updates')->onDelete('set null');
            $table->foreignId('requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->decimal('additional_cost', 10, 2)->nullable();
            $table->unsignedTinyInteger('additional_time_days')->nullable();
            $table->string('status')->default('pending'); // pending, approved, declined, expired
            $table->timestamp('responded_at')->nullable();
            $table->text('response_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_approval_requests');
        Schema::dropIfExists('repair_progress_photos');
        Schema::dropIfExists('repair_progress_updates');

        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn(['current_percentage', 'tracking_token']);
        });
    }
};
