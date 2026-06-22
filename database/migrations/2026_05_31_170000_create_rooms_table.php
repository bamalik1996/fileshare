<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the rooms table per design.md "Data Models" section.
     * Backs Requirement 7 (Room codes) and Requirement 10 (Clipboard sync).
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            // Normalised uppercase, alphabet ABCDEFGHJKLMNPQRSTUVWXYZ23456789
            $table->char('code', 6)->unique();
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_activity_at')->nullable();
            // Up to 500,000 chars (mediumtext fits 16 MB)
            $table->mediumText('clipboard_text')->nullable();
            $table->timestamp('clipboard_updated_at')->nullable();
            $table->timestamps();

            $table->index('expires_at');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
