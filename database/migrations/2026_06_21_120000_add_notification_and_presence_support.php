<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->boolean('notify_browser')->default(false)->after('is_favourite');
            $table->boolean('notify_email')->default(false)->after('notify_browser');
            $table->string('notify_email_address')->nullable()->after('notify_email');
        });

        Schema::create('room_presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('device_id', 64);
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['room_id', 'device_id']);
            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_presences');

        Schema::table('shares', function (Blueprint $table) {
            $table->dropColumn(['notify_browser', 'notify_email', 'notify_email_address']);
        });
    }
};
