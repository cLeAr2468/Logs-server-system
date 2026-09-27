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
            // Add transaction_id foreign key (nullable for backward compatibility)
            $table->unsignedBigInteger('transaction_id')->after('user_id')->nullable();
            
            // Add foreign key constraint
            $table->foreign('transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('cascade');
            
            // Add unique constraint - one feedback per transaction
            $table->unique('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']);
            $table->dropUnique(['transaction_id']);
            $table->dropColumn('transaction_id');
        });
    }
};
