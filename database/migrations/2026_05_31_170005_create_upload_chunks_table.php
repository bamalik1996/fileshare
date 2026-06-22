<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the upload_chunks table per design.md "Data Models" section.
     * Composite unique key (session_id, chunk_index) enforces idempotent re-uploads.
     * Backs Requirement 9 (chunked uploads).
     */
    public function up(): void
    {
        Schema::create('upload_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('upload_sessions')
                ->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->char('sha256', 64);
            // Validated <= 5 MB at the service layer.
            $table->unsignedInteger('size_bytes');
            // chunks/{session_uuid}/{index}.bin
            $table->string('stored_path');
            $table->timestamp('created_at')->nullable();

            $table->unique(['session_id', 'chunk_index'], 'upload_chunks_session_index_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_chunks');
    }
};
