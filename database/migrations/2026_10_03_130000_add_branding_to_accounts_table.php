<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds per-Account branding (SaaS features, Phase 4) that is applied to
     * the Account owner's public share pages and collect/upload pages:
     *
     *  - brand_logo_path : relative path (on the public disk) to the logo.
     *  - brand_color     : accent colour as a #rrggbb hex string.
     *  - brand_message   : short message shown to recipients.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('brand_logo_path')->nullable()->after('email_verified_at');
            $table->string('brand_color', 7)->nullable()->after('brand_logo_path');
            $table->string('brand_message', 160)->nullable()->after('brand_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['brand_logo_path', 'brand_color', 'brand_message']);
        });
    }
};
