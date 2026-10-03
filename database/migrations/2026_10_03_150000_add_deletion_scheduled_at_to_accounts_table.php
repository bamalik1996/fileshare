<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->timestamp('deletion_scheduled_at')->nullable()->after('brand_message');
            $table->index('deletion_scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['deletion_scheduled_at']);
            $table->dropColumn('deletion_scheduled_at');
        });
    }
};
