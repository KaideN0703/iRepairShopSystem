<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photo_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_order_id')->constrained('job_orders')->onDelete('cascade');
            $table->string('photo_type'); // e.g. App\Models\RepairProgressPhoto or App\Models\Attachment
            $table->unsignedBigInteger('photo_id');
            $table->foreignId('parent_id')->nullable()->constrained('photo_comments')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('author_name');
            $table->string('author_type')->default('customer'); // customer, technician, staff
            $table->text('comment');
            $table->timestamps();

            $table->index(['photo_type', 'photo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_comments');
    }
};
