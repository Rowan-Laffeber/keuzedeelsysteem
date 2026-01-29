<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Keuzedeel;
use App\Services\PriorityStatusService;

class StudentController extends Controller
{
    public function index(Request $request)
    {

        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Alleen admins mogen deze pagina bekijken.');
        }
        
        $query = Student::with(['user', 'inschrijvingen.keuzedeel']);
        // ->paginate(15); kijk ernaar om pagination te maken

        // --- Search filter: name, studentnummer, keuzedeel title ---
        if ($request->filled('search')) {
            $search = strtolower($request->search);

            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($q2) use ($search) {
                    $q2->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                       ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
                })
                ->orWhere('studentnummer', 'LIKE', "%{$search}%")
                ->orWhereHas('inschrijvingen.keuzedeel', function($q3) use ($search) {
                    $q3->whereRaw('LOWER(title) LIKE ?', ["%{$search}%"]);
                });
            });
        }

        // --- Filter on keuzedeel ID ---
        if ($request->filled('keuzedeel')) {
            $keuzedeel = $request->keuzedeel;
            $query->whereHas('inschrijvingen', fn($q) => $q->where('keuzedeel_id', $keuzedeel));
        }

        // --- Filter on roostergroep ---
        if ($request->filled('roostergroep')) {
            $query->where('roostergroep', $request->roostergroep);
        }

        // --- Filter on opleiding ---
        if ($request->filled('opleiding')) {
            $query->where('opleidingsnummer', $request->opleiding);
        }

        // --- Only students with at least one inschrijving? Optional ---
        // $query->has('inschrijvingen');

        // --- Execute query ---
        $students = $query->paginate(15)->withQueryString();

        // --- Keuzedelen for dropdown: only where parent_id is not null ---
        $keuzedelen = Keuzedeel::whereNotNull('parent_id')->get();

        // --- Unique roostergroepen and opleidingen for filters ---
        $roostergroepen = Student::distinct()->pluck('roostergroep');
        $opleidingen = Student::distinct()->pluck('opleidingsnummer');

        // --- AJAX response
        if ($request->ajax()) {
            $html = '';
            foreach ($students as $student) {
                $html .= view('students.index-rows', compact('student'))->render();
            }
            return $html;
        }

        // --- Return full view ---
        return view('studentoverzicht', compact('students', 'keuzedelen', 'roostergroepen', 'opleidingen'));
    }



    public function destroy(Student $student)
    {
        $user = auth()->user();

        if ($user->role !== 'admin') {
            return back()->with('error', 'Alleen admins mogen studenten verwijderen.');
        }

        // Collect affected keuzedelen BEFORE deletion
        $keuzedeelIds = $student->inschrijvingen()
            ->pluck('keuzedeel_id')
            ->unique()
            ->toArray();

        // Delete all inschrijvingen
        $student->inschrijvingen()->delete();

        // Delete linked user account
        if ($student->user) {
            $student->user->delete();
        }

        // Delete student record
        $student->delete();

        return redirect()
            ->route('studentoverzicht')
            ->with('success', 'Student, gebruiker en inschrijvingen zijn definitief verwijderd.');
    }
}
