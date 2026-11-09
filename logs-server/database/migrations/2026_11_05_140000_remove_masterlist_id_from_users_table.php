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
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key constraint first if it exists
            $table->dropForeign(['masterlist_id']);
            
            // Then drop the column
            $table->dropColumn('masterlist_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore the column in case of rollback
            $table->unsignedBigInteger('masterlist_id')->nullable()->after('student_id');
            
            // Restore the foreign key
            $table->foreign('masterlist_id')
                  ->references('id')
                  ->on('masterlist')
                  ->onDelete('cascade');
        });
    }
};
