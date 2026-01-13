<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\Docent;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = base_path('tempfiles/Overzicht-keuzedeel-per-student.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");
            return;
        }

        DB::transaction(function () use ($csvPath) {

            // 🔹 FULL RESET
            Student::truncate();
            User::where('role', 'student')->delete();

            $handle = fopen($csvPath, 'r');

            if (!$handle) {
                $this->command->error("Unable to open CSV file: {$csvPath}");
                return;
            }

            // Detect delimiter (; or ,)
            $firstLine = fgets($handle);
            $delimiter = str_contains($firstLine, ';') ? ';' : ',';
            rewind($handle);

            // Skip any rows until the header row with "student"
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (in_array('student', $row) || in_array('student', array_map('strtolower', $row))) {
                    $header = $row;
                    break;
                }
            }

            if (!isset($header)) {
                $this->command->error("CSV header row with 'student' column not found.");
                fclose($handle);
                return;
            }

            // Import each student row
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {

                if (!$row || count($row) < 4) continue;

                $row = array_map('trim', $row);
                $data = array_combine($header, $row);

                $studentnummer = $data['student'] ?? null;
                $name = $data['naam'] ?? null;
                $opleidingsnummer = $data['Opleidings Code'] ?? null;
                $cohortYearRaw = $data['cohort'] ?? null;

                if (empty($studentnummer) || empty($name)) continue;

                // Cohort year: take the first 4 digits of something like "2022/2023"
                $cohortYear = intval(substr($cohortYearRaw, 0, 4));

                $user = User::create([
                    'id' => (string) Str::uuid(),
                    'name' => $name,
                    'email' => $studentnummer . '@student.school.nl',
                    'password' => Hash::make('password'),
                    'role' => 'student',
                ]);

                Student::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'studentnummer' => $studentnummer,
                    'opleidingsnummer' => $opleidingsnummer,
                    'cohort_year' => $cohortYear,
                ]);
            }

            fclose($handle);

            $this->command->info("✅ Students imported successfully.");
        });

        // 🔹 Add docent
        $docentUser = User::firstOrCreate(
            ['email' => 'docent@docent.nl'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'docent',
                'password' => Hash::make('password'),
                'role' => 'docent',
            ]
        );

        Docent::firstOrCreate(
            ['user_id' => $docentUser->id],
            [
                'id' => (string) Str::uuid(),
                'afkorting' => 'DVR',
            ]
        );

        // 🔹 Add admin
        User::firstOrCreate(
            ['email' => 'admin@admin.nl'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $this->command->info("✅ Docent and admin added.");
    }
}
