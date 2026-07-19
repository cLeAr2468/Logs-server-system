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
        Schema::create('exported_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->string('report_name');
            $table->string('report_type');
            $table->string('file_format'); // csv, excel, pdf
            $table->string('file_path');
            $table->string('file_size')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('include_summary')->default(false);
            $table->boolean('include_details')->default(false);
            $table->boolean('include_feedback')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exported_reports');
    }
};
