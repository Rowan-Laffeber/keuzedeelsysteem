<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Docent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Add admin user
        User::firstOrCreate(
            ['email' => 'admin@admin.nl'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // 🔹 Add docent user
        $docentUser = User::firstOrCreate(
            ['email' => 'docent@docent.nl'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'docent',
                'password' => Hash::make('password'),
                'role' => 'docent',
            ]
        );

        // 🔹 Add corresponding docent record
        Docent::firstOrCreate(
            ['user_id' => $docentUser->id],
            [
                'id' => (string) Str::uuid(),
                'afkorting' => 'DVR',
            ]
        );

        // 🔹 Add student user
           $studentUser = User::firstOrCreate(
            ['email' => '1234567@student.school.nl'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Test Student',
                'password' => Hash::make('password'),
                'role' => 'student',
            ]
        );

        // 🔹 Add corresponding student record
        \App\Models\Student::firstOrCreate(
            ['user_id' => $studentUser->id],
            [
                'id' => (string) Str::uuid(),
                'studentnummer' => '1234567',
                'opleidingsnummer' => '123456',
                'cohort_year' => '2024',
                'roostergroep' => 'A1',
            ]
        );

        $this->command->info('✅ Admin, docent, and student created.');
    }
}
