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
        Schema::table('announcements', function (Blueprint $table) {
            // Drop the old foreign key constraint if it exists
            $table->dropForeign(['user_id']);
            
            // Rename the column from user_id to staff_id
            $table->renameColumn('user_id', 'staff_id');
        });

        // Add the new foreign key constraint in a separate statement
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            // Drop the staff foreign key
            $table->dropForeign(['staff_id']);
            
            // Rename back to user_id
            $table->renameColumn('staff_id', 'user_id');
        });

        // Add back the original foreign key
        Schema::table('announcements', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
