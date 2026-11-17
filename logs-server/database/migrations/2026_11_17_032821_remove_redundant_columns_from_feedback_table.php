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
        Schema::table('feedback', function (Blueprint $table) {
            // Remove redundant denormalized columns - use pure relationships instead
            $table->dropColumn(['transaction_purpose', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            // Restore columns if needed
            $table->string('transaction_purpose')->nullable();
            $table->string('transaction_date')->nullable();
        });
    }
};
