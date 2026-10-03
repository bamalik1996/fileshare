<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds owner-controlled link controls to Shares (SaaS features, Phase 2):
     *
     *  - max_downloads   : optional ceiling on total downloads of the Share's
     *                      files. null = unlimited. A value of 1 implements the
     *                      "burn after reading" behaviour.
     *  - download_count  : running total of successful downloads, incremented
     *                      atomically on the /download/{uuid} path.
     *  - revoked_at      : when set, the Share's links stop working immediately
     *                      (download → 410, public/recipient views → 404).
     */
    public function up(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->unsignedInteger('max_downloads')->nullable()->after('public_view_count');
            $table->unsignedInteger('download_count')->default(0)->after('max_downloads');
            $table->timestamp('revoked_at')->nullable()->after('download_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropColumn(['max_downloads', 'download_count', 'revoked_at']);
        });
    }
};
