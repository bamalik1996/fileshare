<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `share_events` table that powers per-Share download/view
     * analytics and the audit log (SaaS features bundle, Phase 1).
     *
     * Privacy: we never persist a raw client IP. Instead we store a salted,
     * truncated hash (`ip_hash`) so repeat visitors can be de-duplicated for
     * unique-visitor counts without the table ever holding PII.
     */
    public function up(): void
    {
        Schema::create('share_events', function (Blueprint $table) {
            $table->id();

            // Owning Share. Cascade so events vanish with their Share.
            $table->foreignId('share_id')
                ->constrained('shares')
                ->cascadeOnDelete();

            // Spatie media UUID for download events; null for view events.
            $table->char('media_uuid', 36)->nullable();

            // 'view' | 'download'.
            $table->string('event_type', 16);

            // Salted + truncated hash of the client IP. Never the raw IP.
            $table->string('ip_hash', 64)->nullable();

            // Coarse geo + client metadata derived at request time.
            $table->string('country', 2)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('device', 32)->nullable();
            $table->string('browser', 48)->nullable();
            $table->string('os', 48)->nullable();
            $table->string('referrer', 255)->nullable();

            // Only created_at matters; events are immutable.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['share_id', 'created_at']);
            $table->index(['share_id', 'event_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_events');
    }
};
