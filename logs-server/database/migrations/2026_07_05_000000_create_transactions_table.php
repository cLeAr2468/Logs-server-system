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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('purpose');
            $table->string('brgy');
            $table->string('municipality');
            $table->string('province');
            $table->string('schedule_date');
            $table->string('time_slot');
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled', 'rejected'])->default('pending');
            $table->timestamps();
        });
        
        // password_reset_tokens table is now created in a separate migration
        // See: 2026_07_03_082054_create_password_reset_tokens_table.php
    }

    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};