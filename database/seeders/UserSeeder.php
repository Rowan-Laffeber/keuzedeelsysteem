<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Docent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // --- STUDENT ---
        $studentUser = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'student',
            'email' => 'student@student.nl',
            'password' => Hash::make('student'),
            'role' => 'student',
        ]);

        Student::create([
            'id' => (string) Str::uuid(),
            'user_id' => $studentUser->id,
            'studentnummer' => 'S001',
        ]);

        // --- STUDENT 2 ---
        $student2User = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'student2',
            'email' => 'student2@student.nl',
            'password' => Hash::make('student'),
            'role' => 'student',
        ]);

        Student::create([
            'id' => (string) Str::uuid(),
            'user_id' => $student2User->id,
            'studentnummer' => 'S002',
        ]);

        // --- DOCENT ---
        $docentUser = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'docent',
            'email' => 'docent@docent.nl',
            'password' => Hash::make('docent'),
            'role' => 'docent',
        ]);

        Docent::create([
            'id' => (string) Str::uuid(),
            'user_id' => $docentUser->id,
            'afkorting' => 'DVR',
        ]);

        // --- ADMIN ---
        $adminUser = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'admin',
            'email' => 'admin@admin.nl',
            'password' => Hash::make('admin'),
            'role' => 'admin',
        ]);

        // Admin heeft geen extra model
    }
}
