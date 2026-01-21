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
    public function index()
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Alleen admins mogen deze pagina bekijken.');
        }

        return view('upload');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Alleen admins mogen deze pagina bekijken.');
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $uploadedFilePath = $this->upload($request->file('csv_file'));

        if (!$uploadedFilePath) {
            return back()->with('error', 'Fout bij uploaden van CSV bestand.');
        }

        $importCount = $this->importStudents($uploadedFilePath);

        return back()->with('success', "CSV succesvol geüpload. {$importCount} studenten aangemaakt");
    }

    protected function upload($file)
    {
        $uploadFolder = storage_path('app/csv_uploads');

        if (!is_dir($uploadFolder)) {
            mkdir($uploadFolder, 0777, true);
        }

        $filename = time() . '_' . $file->getClientOriginalName();
        $fullPath = $file->move($uploadFolder, $filename);

        return file_exists($fullPath) ? $fullPath : false;
    }

    protected function importStudents($fullPath)
    {
        $importedCount = 0;

        DB::transaction(function () use ($fullPath, &$importedCount) {
            $handle = fopen($fullPath, 'r');
            if (!$handle) {
                throw new \Exception('Unable to open CSV file.');
            }

            $firstLine = fgets($handle);
            $delimiter = str_contains($firstLine, ';') ? ';' : ',';
            rewind($handle);

            $header = null;
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowLower = array_map('strtolower', array_map('trim', $row));
                if (in_array('roostergroep', $rowLower)) {
                    $header = $rowLower;
                    break;
                }
            }

            if (!$header) {
                fclose($handle);
                throw new \Exception("CSV header with 'roostergroep' column not found.");
            }

            $lastRoostergroep = null;

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (!$row || count($row) < 4) continue;

                $row = array_map('trim', $row);
                $data = array_combine($header, $row);

                $roostergroep = $data['roostergroep'] ?? '';
                if ($roostergroep === '' || $roostergroep === null) {
                    $roostergroep = $lastRoostergroep ?? 'onbekend';
                } else {
                    $lastRoostergroep = $roostergroep;
                }

                $studentnummer = $data['student'] ?? null;
                $name = $data['naam'] ?? null;
                $opleidingsnummer = $data['opleidings code'] ?? null;
                $cohortYearRaw = $data['cohort'] ?? null;

                if (empty($studentnummer) || empty($name)) continue;

                // FIX: keep cohort as string
                $cohortYear = $cohortYearRaw !== null ? trim($cohortYearRaw) : null;

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
                        'roostergroep' => $roostergroep,
                    ]
                );

                $importedCount++;
            }

            fclose($handle);
        });

        return $importedCount;
    }
}
