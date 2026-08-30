<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin already exists
        $existingAdmin = DB::table('admins')->where('email', 'admin@nwssu.edu.ph')->first();
        
        if (!$existingAdmin) {
            DB::table('admins')->insert([
                'admin_id' => 'ADMIN-001',
                'fname' => 'System',
                'mname' => '',
                'lname' => 'Administrator',
                'email' => 'admin@nwssu.edu.ph',
                'password' => Hash::make('admin123'),
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Admin account created successfully!');
            $this->command->info('Admin ID: ADMIN-001');
            $this->command->info('Email: admin@nwssu.edu.ph');
            $this->command->info('Password: admin123');
        } else {
            $this->command->warn('Admin account already exists.');
        }
    }
}
