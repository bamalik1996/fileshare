<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Builds an in-memory `shares` table that matches the live Share model
 * attributes (including notify_*, link-control, and collect columns).
 *
 * Inline-schema unit/feature tests should call this instead of duplicating
 * a partial Blueprint that drifts every time Share gains a defaulted column.
 */
trait CreatesSharesSchema
{
    protected function createSharesSchema(bool $withOwnerIndexes = false): void
    {
        Schema::create('shares', function (Blueprint $table) use ($withOwnerIndexes) {
            $table->id();
            $table->char('uuid', 36)->unique();
            $table->string('owner_type');
            $table->string('owner_id');
            $table->longText('text_content')->nullable();
            $table->longText('markdown_source')->nullable();
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at');
            $table->char('public_slug', 12)->nullable()->unique();
            $table->unsignedInteger('public_view_count')->default(0);
            $table->unsignedInteger('max_downloads')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('revoked_at')->nullable();
            $table->boolean('is_collect')->default(false);
            $table->string('collect_slug', 16)->nullable()->unique();
            $table->string('collect_title', 120)->nullable();
            $table->string('collect_instructions', 500)->nullable();
            $table->boolean('is_e2ee')->default(false);
            $table->boolean('is_favourite')->default(false);
            $table->boolean('notify_browser')->default(false);
            $table->boolean('notify_email')->default(false);
            $table->string('notify_email_address')->nullable();
            $table->timestamps();

            if ($withOwnerIndexes) {
                $table->index(['owner_type', 'owner_id', 'expires_at']);
                $table->index('expires_at');
            }
        });

        // Share::deleting() + NotificationService::rearmOnExpiryChange()
        // query this table; without it, deletes/updates explode in tests.
        if (! Schema::hasTable('share_notifications')) {
            $this->createShareNotificationsSchema();
        }
    }

    /**
     * Minimal Spatie media table for Share::clearMediaCollection() / HasMedia.
     */
    protected function createMediaSchema(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Required because Share::deleting() / NotificationService touch this table.
     */
    protected function createShareNotificationsSchema(): void
    {
        Schema::create('share_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('share_id');
            $table->timestamp('cycle_expires_at');
            $table->string('channel');
            $table->timestamp('send_at');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }
}
