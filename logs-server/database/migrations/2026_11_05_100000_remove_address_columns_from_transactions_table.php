<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove redundant address columns from transactions table.
     * Address info should be retrieved from users table via relationship.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Drop redundant address columns
            $table->dropColumn(['brgy', 'municipality', 'province']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Restore columns if rollback is needed
            $table->string('brgy')->after('purpose');
            $table->string('municipality')->after('brgy');
            $table->string('province')->after('municipality');
        });
    }
};
