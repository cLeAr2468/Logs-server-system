<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add timestamp columns to track when each status was set
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('release_date');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->timestamp('cancelled_at')->nullable()->after('rejected_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'rejected_at', 'cancelled_at']);
        });
    }
};
