<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `shares` aggregate table introduced by the
     * fileshare-enhancements-bundle spec (design.md > Data Models > shares).
     *
     * Replaces the per-feature `shared_texts` / `media_files` tables for new
     * code paths. The legacy tables remain in place during the deprecation
     * window.
     */
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();

            // External identifier used in URLs and broadcast payloads.
            $table->char('uuid', 36)->unique();

            // Polymorphic owner: `ip` | `room` | `account`. `owner_id` is a
            // string column so IP literals (e.g. "192.168.1.10") and numeric
            // foreign keys (rooms / accounts) can share the column.
            $table->string('owner_type');
            $table->string('owner_id');

            // Server-rendered HTML for Markdown shares OR plaintext.
            $table->longText('text_content')->nullable();

            // Markdown editing source, capped at 500,000 chars by validation.
            $table->longText('markdown_source')->nullable();

            // bcrypt hash for password-protected shares (Requirement 2).
            $table->string('password_hash')->nullable();

            // Absolute expiry timestamp in UTC, second precision (Requirement 3.3).
            $table->timestamp('expires_at');

            // Opt-in public gallery slug (Requirement 17).
            $table->char('public_slug', 12)->nullable()->unique();
            $table->unsignedInteger('public_view_count')->default(0);

            // Toggles for end-to-end encryption (Requirement 15) and
            // account-side favouriting (Requirement 16.7).
            $table->boolean('is_e2ee')->default(false);
            $table->boolean('is_favourite')->default(false);

            $table->timestamps();

            // Composite owner lookup (used for active-share queries).
            $table->index(['owner_type', 'owner_id', 'expires_at']);

            // Standalone expiry index for the cleanup scheduler.
            $table->index('expires_at');

            // Note: `public_slug` already has a unique index from ->unique()
            // above, which Laravel/MySQL will reuse for slug lookups, so no
            // additional non-unique index is created.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
