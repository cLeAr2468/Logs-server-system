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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_type'); // 'admin' or 'staff'
            $table->unsignedBigInteger('user_id'); // ID from admins or staff table
            $table->string('user_name'); // Full name for display
            $table->string('action'); // e.g., 'created', 'updated', 'deleted', 'logged_in', 'logged_out'
            $table->string('module'); // e.g., 'transaction', 'announcement', 'user', 'staff', 'masterlist'
            $table->text('description'); // Human-readable description
            $table->json('metadata')->nullable(); // Additional data as JSON
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index(['user_type', 'user_id']);
            $table->index('created_at');
            $table->index('module');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
