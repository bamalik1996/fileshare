<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the media_scans table per design.md "Data Models" section.
     * Backs Requirement 20 (Virus scanner).
     */
    public function up(): void
    {
        Schema::create('media_scans', function (Blueprint $table) {
            $table->id();
            // References Spatie media.uuid (not enforced at DB level because the
            // Spatie media table predates the FK style we use here).
            $table->char('media_uuid', 36)->unique();
            // pending | clean | infected | error | skipped_e2ee
            $table->string('status');
            // clamav | virustotal
            $table->string('backend');
            $table->unsignedInteger('retry_count')->default(0);
            $table->json('result_payload')->nullable();
            $table->timestamp('queued_at');
            $table->timestamp('scanned_at')->nullable();

            $table->index('status');
            $table->index('queued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_scans');
    }
};
