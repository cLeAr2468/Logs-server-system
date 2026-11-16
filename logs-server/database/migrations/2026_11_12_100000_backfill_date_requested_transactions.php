<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfill date_requested for existing transactions that don't have it
     */
    public function up(): void
    {
        // Update transactions where date_requested is null
        // Set date_requested to created_at as a fallback
        DB::table('transactions')
            ->whereNull('date_requested')
            ->update([
                'date_requested' => DB::raw('created_at')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - this is a data backfill
        // We don't want to set date_requested back to null
    }
};
