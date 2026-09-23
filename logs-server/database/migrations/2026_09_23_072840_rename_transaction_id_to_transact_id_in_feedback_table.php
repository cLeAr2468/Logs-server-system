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
        // First, drop constraints
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'transaction_id']);
            $table->dropForeign(['transaction_id']);
        });
        
        // Rename column using raw SQL
        \DB::statement('ALTER TABLE feedback CHANGE transaction_id transact_id BIGINT UNSIGNED NULL');
        
        // Re-add constraints with new column name
        Schema::table('feedback', function (Blueprint $table) {
            $table->foreign('transact_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->unique(['user_id', 'transact_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            // Drop foreign key and unique constraint
            $table->dropForeign(['transact_id']);
            $table->dropUnique(['user_id', 'transact_id']);
            
            // Rename back
            $table->renameColumn('transact_id', 'transaction_id');
        });
        
        // Re-add original constraints
        Schema::table('feedback', function (Blueprint $table) {
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->unique(['user_id', 'transaction_id']);
        });
    }
};
