<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Keuzedeel;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        // Begin query met eager loading
        $query = Student::with(['user', 'inschrijvingen.keuzedeel']);

        // --- Search filter: naam, studentnummer, keuzedeel title ---
        if ($request->filled('search')) {
            $search = strtolower($request->search);

            $query->where(function($q) use ($search) {
                // Zoek in naam/email
                $q->whereHas('user', function($q2) use ($search) {
                    $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                       ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                })
                // Of studentnummer
                ->orWhere('studentnummer', 'LIKE', "%{$search}%")
                // Of inschrijvingen -> keuzedeel title
                ->orWhereHas('inschrijvingen.keuzedeel', function($q3) use ($search) {
                    $q3->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"]);
                });
            });
        }

        // --- Filter op keuzedeel ID ---
        if ($request->filled('keuzedeel')) {
            $keuzedeel = $request->keuzedeel;
            $query->whereHas('inschrijvingen', fn($q) => $q->where('keuzedeel_id', $keuzedeel));
        }

        // --- Filter op roostergroep ---
        if ($request->filled('roostergroep')) {
            $query->where('roostergroep', $request->roostergroep);
        }

        // --- Filter op opleiding ---
        if ($request->filled('opleiding')) {
            $query->where('opleidingsnummer', $request->opleiding);
        }

        // --- Haal studenten op ---
        $students = $query->get();

        // --- Keuzedelen voor dropdown: alleen parent_id != null ---
        $keuzedelen = Keuzedeel::whereNotNull('parent_id')->get();

        // --- Unieke roostergroepen en opleidingen voor filters ---
        $roostergroepen = Student::distinct()->pluck('roostergroep');
        $opleidingen = Student::distinct()->pluck('opleidingsnummer');

        // --- AJAX response ---
        if ($request->ajax()) {
            $html = '';
            foreach ($students as $student) {
                $html .= view('students.index-rows', compact('student'))->render();
            }
            return $html;
        }

        // --- Return view ---
        return view('studentoverzicht', compact('students', 'keuzedelen', 'roostergroepen', 'opleidingen'));
    }
}
