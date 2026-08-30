<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('status')->default('Active');
            $table->timestamps();
        });

        // Insert default admin account
        DB::table('admins')->insert([
            'fname' => 'System',
            'mname' => '',
            'lname' => 'Administrator',
            'email' => 'admin@nwssu.edu.ph',
            'password' => Hash::make('admin'),
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
