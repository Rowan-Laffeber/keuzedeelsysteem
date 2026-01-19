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

        $this->command->info('✅ Admin and docent created.');
    }
}
