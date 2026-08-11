<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Task 8 — Signature Capture Fallback:
     * Add JSON metadata column to attachments for signature_type flag.
     */
    public function up(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
