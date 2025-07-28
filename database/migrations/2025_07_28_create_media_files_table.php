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
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type');
            $table->string('storage_path');
            $table->string('ip_address')->index();
            $table->unsignedBigInteger('file_size');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index(['ip_address', 'expires_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};