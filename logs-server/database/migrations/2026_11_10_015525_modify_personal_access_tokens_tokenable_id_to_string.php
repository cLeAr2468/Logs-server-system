<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete all existing tokens first
        DB::table('personal_access_tokens')->truncate();
        
        // Modify tokenable_id to support string IDs (for users with student_id as primary key)
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('tokenable_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete all tokens again
        DB::table('personal_access_tokens')->truncate();
        
        // Revert tokenable_id back to unsigned big integer
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tokenable_id')->change();
        });
    }
};
