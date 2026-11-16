<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfill status timestamps for existing transactions
     */
    public function up(): void
    {
        // Backfill approved_at for approved transactions
        DB::table('transactions')
            ->where('status', 'approved')
            ->whereNull('approved_at')
            ->update([
                'approved_at' => DB::raw('updated_at')
            ]);
        
        // Backfill rejected_at for rejected transactions
        DB::table('transactions')
            ->where('status', 'rejected')
            ->whereNull('rejected_at')
            ->update([
                'rejected_at' => DB::raw('updated_at')
            ]);
        
        // Backfill cancelled_at for cancelled transactions
        DB::table('transactions')
            ->where('status', 'cancelled')
            ->whereNull('cancelled_at')
            ->update([
                'cancelled_at' => DB::raw('updated_at')
            ]);
        
        // Backfill release_date for completed transactions that don't have it
        DB::table('transactions')
            ->where('status', 'completed')
            ->whereNull('release_date')
            ->update([
                'release_date' => DB::raw('updated_at')
            ]);
        
        // For completed transactions, also set approved_at if they were approved before
        // We'll use updated_at as a fallback since we don't have the exact approval timestamp
        DB::table('transactions')
            ->where('status', 'completed')
            ->whereNull('approved_at')
            ->update([
                'approved_at' => DB::raw('updated_at')
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse - this is a data backfill
    }
};
