<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the upload_sessions table per design.md "Data Models" section.
     * Backs Requirement 9 (chunked uploads).
     */
    public function up(): void
    {
        Schema::create('upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->char('uuid', 36)->unique();
            // share_id is nullable: a session may be started before the Share row exists,
            // and the FK is wired in once the assemble step runs.
            $table->foreignId('share_id')
                ->nullable()
                ->constrained('shares')
                ->nullOnDelete();
            $table->string('filename');
            $table->string('mime');
            // Validated <= 500 MB at the service layer.
            $table->unsignedBigInteger('total_bytes');
            // Validated 1..1000 at the service layer.
            $table->unsignedInteger('total_chunks');
            $table->timestamp('first_chunk_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('first_chunk_at');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_sessions');
    }
};
