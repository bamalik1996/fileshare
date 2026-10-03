<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds "file request / collect" support to Shares (SaaS features,
     * Phase 3). A collect Share is owned by an Account but is filled by
     * recipients who upload files to it via a public /collect/{slug} page -
     * the inverse of normal sharing.
     *
     *  - is_collect           : marks the Share as a collect inbox.
     *  - collect_slug         : public, URL-safe code for the upload page.
     *  - collect_title        : optional heading shown to uploaders.
     *  - collect_instructions : optional guidance shown to uploaders.
     */
    public function up(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->boolean('is_collect')->default(false)->after('revoked_at');
            $table->string('collect_slug', 16)->nullable()->unique()->after('is_collect');
            $table->string('collect_title', 120)->nullable()->after('collect_slug');
            $table->string('collect_instructions', 500)->nullable()->after('collect_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropColumn(['is_collect', 'collect_slug', 'collect_title', 'collect_instructions']);
        });
    }
};
