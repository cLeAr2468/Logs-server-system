<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add foreign key constraint to users.masterlist_id after masterlist table exists
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('masterlist_id')
                  ->references('id')
                  ->on('masterlist')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['masterlist_id']);
        });
    }
};
