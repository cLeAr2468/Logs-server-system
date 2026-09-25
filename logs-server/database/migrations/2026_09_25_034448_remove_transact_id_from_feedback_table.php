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
            // Check if constraints exist and drop them
            try {
                // Drop foreign key if exists
                $table->dropForeign(['transact_id']);
            } catch (\Exception $e) {
                // Constraint doesn't exist, continue
            }
            
            try {
                // Drop unique constraint if exists  
                $table->dropUnique(['user_id', 'transact_id']);
            } catch (\Exception $e) {
                // Constraint doesn't exist, continue
            }
            
            // Drop the column if it exists
            if (Schema::hasColumn('feedback', 'transact_id')) {
                $table->dropColumn('transact_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            // Re-add the column
            $table->unsignedBigInteger('transact_id')->after('user_id')->nullable();
            
            // Re-add foreign key and unique constraint
            $table->foreign('transact_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->unique(['user_id', 'transact_id']);
        });
    }
};
