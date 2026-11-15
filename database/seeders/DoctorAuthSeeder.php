<?php

// database/seeders/DoctorAuthSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use Illuminate\Support\Facades\Hash;

class DoctorAuthSeeder extends Seeder
{
    public function run(): void
    {
        $doctor = Doctor::first();
        
        if ($doctor && !$doctor->email) {
            $doctor->update([
                'email' => 'doctor@test.com',
                'password' => Hash::make('password'),
                'phone' => '09121234567',
            ]);
            $this->command->info('✅ اطلاعات ورود پزشک:');
            $this->command->info('Email: doctor@test.com');
            $this->command->info('Password: password');
        }
    }
}