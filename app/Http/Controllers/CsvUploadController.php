<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;

class CsvUploadController extends Controller
{
    /**
     * Show the upload form.
     */
    public function index()
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Alleen admins mogen deze pagina bekijken.');
        }

        return view('upload'); // resources/views/upload.blade.php
    }

    /**
     * Handles the file upload and calls importStudents.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Alleen admins mogen deze pagina bekijken.');
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        // Upload file
        $uploadedFilePath = $this->upload($request->file('csv_file'));

        if (!$uploadedFilePath) {
            return back()->with('error', 'Fout bij uploaden van CSV bestand.');
        }

        // Import students from CSV
        $importCount = $this->importStudents($uploadedFilePath);

        return back()->with('success', "CSV succesvol geüpload. {$importCount} studenten aangemaakt of bijgewerkt.");
    }

    /**
     * Uploads the file to storage/app/csv_uploads and returns full path.
     */
    protected function upload($file)
    {
        $uploadFolder = storage_path('app/csv_uploads');

        // Ensure folder exists
        if (!is_dir($uploadFolder)) {
            mkdir($uploadFolder, 0777, true);
        }

        $filename = time() . '_' . $file->getClientOriginalName();
        $fullPath = $file->move($uploadFolder, $filename);

        if (file_exists($fullPath)) {
            return $fullPath;
        }

        return false;
    }

    /**
     * Reads CSV file and creates/updates students.
     * Returns the number of students imported.
     */
    protected function importStudents($fullPath)
    {
        $importedCount = 0;

        DB::transaction(function () use ($fullPath, &$importedCount) {
            $handle = fopen($fullPath, 'r');
            if (!$handle) {
                throw new \Exception('Unable to open CSV file.');
            }

            // Detect delimiter
            $firstLine = fgets($handle);
            $delimiter = str_contains($firstLine, ';') ? ';' : ',';
            rewind($handle);

            // Find header row containing 'student'
            $header = null;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowLower = array_map('strtolower', $row);
                if (in_array('student', $rowLower)) {
                    $header = $row;
                    break;
                }
            }

            if (!$header) {
                fclose($handle);
                throw new \Exception("CSV header with 'student' column not found.");
            }

            // Process each student row
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (!$row || count($row) < 4) continue;

                $row = array_map('trim', $row);
                $data = array_combine($header, $row);

                $studentnummer = $data['student'] ?? null;
                $name = $data['naam'] ?? null;
                $opleidingsnummer = $data['Opleidings Code'] ?? null;
                $cohortYearRaw = $data['cohort'] ?? null;

                if (empty($studentnummer) || empty($name)) continue;

                $cohortYear = intval(substr($cohortYearRaw, 0, 4));

                $user = User::firstOrCreate(
                    ['email' => $studentnummer . '@student.school.nl'],
                    [
                        'id' => (string) Str::uuid(),
                        'name' => $name,
                        'password' => Hash::make('password'),
                        'role' => 'student',
                    ]
                );

                Student::firstOrCreate(
                    ['studentnummer' => $studentnummer],
                    [
                        'id' => (string) Str::uuid(),
                        'user_id' => $user->id,
                        'opleidingsnummer' => $opleidingsnummer,
                        'cohort_year' => $cohortYear,
                    ]
                );

                $importedCount++;
            }

            fclose($handle);
        });

        return $importedCount;
    }
}
