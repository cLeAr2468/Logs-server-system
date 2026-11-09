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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique();
            
            // Masterlist relationship - foreign key will be added in a separate migration
            // after masterlist table is created
            $table->unsignedBigInteger('masterlist_id')->nullable();
            
            // Address fields (encrypted via model casting)
            $table->string('suffix')->nullable(); // Jr., Sr., II, III, etc.
            $table->text('region')->nullable();
            $table->text('province')->nullable();
            $table->text('municipality')->nullable();
            $table->text('barangay')->nullable();
            
            $table->string('status');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // password_reset_tokens table is now created in a separate migration
        // See: 2026_07_03_082054_create_password_reset_tokens_table.php

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
