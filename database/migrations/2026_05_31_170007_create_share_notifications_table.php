<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the share_notifications table per design.md "Data Models" section.
     * Composite unique key (share_id, cycle_expires_at, channel) enforces once-per-cycle.
     * Backs Requirement 11 (Notifications).
     */
    public function up(): void
    {
        Schema::create('share_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_id')
                ->constrained('shares')
                ->cascadeOnDelete();
            // The share's expires_at value at arming time.
            $table->timestamp('cycle_expires_at');
            // browser | email
            $table->string('channel');
            // cycle_expires_at - 60 minutes (or now() if < 60 min away).
            $table->timestamp('send_at');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->unique(
                ['share_id', 'cycle_expires_at', 'channel'],
                'share_notifications_share_cycle_channel_unique'
            );
            $table->index('send_at');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_notifications');
    }
};
