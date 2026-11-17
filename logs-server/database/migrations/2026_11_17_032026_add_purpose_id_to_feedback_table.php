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
            // Add purpose_id foreign key for pure relationship
            $table->unsignedBigInteger('purpose_id')->nullable()->after('transaction_id');
            $table->foreign('purpose_id')->references('id')->on('purposes')->onDelete('set null');
            
            // Keep transaction_purpose and transaction_date for historical record
            // but purpose_id is the source of truth for reporting
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['purpose_id']);
            $table->dropColumn('purpose_id');
        });
    }
};
