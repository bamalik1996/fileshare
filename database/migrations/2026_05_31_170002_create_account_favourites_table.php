<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the account_favourites pivot table per design.md "Data Models" section.
     * Composite unique key (account_id, share_id). Application enforces <= 50 per account.
     * Backs Requirements 16.7, 16.8.
     */
    public function up(): void
    {
        Schema::create('account_favourites', function (Blueprint $table) {
            $table->foreignId('account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();
            $table->foreignId('share_id')
                ->constrained('shares')
                ->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['account_id', 'share_id'], 'account_favourites_account_share_unique');
            $table->index('share_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_favourites');
    }
};
