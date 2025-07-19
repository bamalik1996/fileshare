<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shared_texts', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('ip_address')->unique(); // IP address (unique)
            $table->text('content')->nullable(); // Content to store text
            $table->timestamp('last_accessed')->default(now()); // last_accessed timestamp
            $table->timestamp('expires_at')->nullable(); // expiry timestamp
            $table->timestamps(); // created_at and updated_at columns
            
            // Add indexes for better performance
            $table->index('expires_at');
            $table->index('last_accessed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_texts');
    }
};
