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
            $table->string('transaction_purpose')->after('user_id')->nullable();
            $table->string('transaction_date')->after('transaction_purpose')->nullable();
            
            // Add index for faster lookups
            $table->index(['user_id', 'transaction_purpose', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'transaction_purpose', 'transaction_date']);
            $table->dropColumn(['transaction_purpose', 'transaction_date']);
        });
    }
};
