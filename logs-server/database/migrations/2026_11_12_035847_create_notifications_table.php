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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_type'); // 'client' or 'admin'
            $table->string('recipient_id'); // student_id or admin_id or staff_id
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->string('type'); // 'new_request', 'approved', 'rejected', 'completed', 'cancelled'
            $table->string('title');
            $table->text('message');
            $table->text('reason')->nullable(); // for rejected/cancelled
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            
            $table->index(['recipient_type', 'recipient_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
