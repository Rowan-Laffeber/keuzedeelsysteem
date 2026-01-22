<?php

namespace App\Http\Controllers;

use App\Models\MoreOption;
use App\Models\Keuzedeel;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MoreOptionsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->role !== 'student') {
            return redirect()->route('home')->with('error', 'Alleen studenten mogen keuzes opgeven.');
        }

        $student = $user->student;
        
        // Get student's current choices
        $choices = $student->moreOptions()
            ->with('keuzedeel')
            ->orderBy('priority')
            ->get()
            ->keyBy('priority');

        // Get all available keuzedelen for dropdowns
        $availableKeuzedelen = Keuzedeel::where('is_open', true)
            ->orderBy('title')
            ->get();

        // Get student's current enrollments to exclude them
        $enrolledKeuzedeelIds = $student->bevestigdeKeuzedelen()
            ->pluck('keuzedelen.id')
            ->toArray();

        // Filter out already enrolled keuzedelen
        $availableKeuzedelen = $availableKeuzedelen
            ->reject(function ($keuzedeel) use ($enrolledKeuzedeelIds) {
                return in_array($keuzedeel->id, $enrolledKeuzedeelIds);
            });

        return view('more-options', compact('choices', 'availableKeuzedelen'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role !== 'student') {
            return redirect()->route('home')->with('error', 'Alleen studenten mogen keuzes opgeven.');
        }

        $student = $user->student;

        // Validate the request
        $request->validate([
            'first_choice' => 'required|exists:keuzedelen,id',
            'second_choice' => 'required|exists:keuzedelen,id|different:first_choice',
            'third_choice' => 'required|exists:keuzedelen,id|different:first_choice,second_choice',
        ]);

        // Clear existing choices
        $student->moreOptions()->delete();

        // Create new choices
        $choices = [
            1 => $request->input('first_choice'),
            2 => $request->input('second_choice'),
            3 => $request->input('third_choice'),
        ];

        foreach ($choices as $priority => $keuzedeelId) {
            MoreOption::create([
                'id' => Str::uuid(),
                'student_id' => $student->id,
                'keuzedeel_id' => $keuzedeelId,
                'priority' => $priority,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('more-options.index')
            ->with('success', 'Je keuzes zijn succesvol opgeslagen!');
    }

    public function processChoices()
    {
        // This method would be called by an admin or scheduled job
        // to process all pending choices and assign students
        
        $pendingChoices = MoreOption::with(['student', 'keuzedeel'])
            ->where('status', 'pending')
            ->orderBy('priority')
            ->get()
            ->groupBy('student_id');

        foreach ($pendingChoices as $studentId => $choices) {
            $this->assignStudentToBestChoice($choices);
        }
    }

    private function assignStudentToBestChoice($choices)
    {
        foreach ($choices as $choice) {
            $keuzedeel = $choice->keuzedeel;
            
            // Check if keuzedeel meets minimum requirements
            if ($this->meetsMinimumRequirements($keuzedeel)) {
                // Assign student to this keuzedeel
                $this->assignStudent($choice->student, $keuzedeel);
                
                // Mark this choice as assigned
                $choice->status = 'assigned';
                $choice->save();
                
                // Reject other choices for this student
                $this->rejectOtherChoices($choice->student_id, $choice->priority);
                return;
            }
        }
        
        // If no choice meets requirements, mark all as rejected
        foreach ($choices as $choice) {
            $choice->status = 'rejected';
            $choice->save();
        }
    }

    private function meetsMinimumRequirements($keuzedeel)
    {
        // Check if keuzedeel has minimum students
        $currentEnrollment = $keuzedeel->ingeschreven_count;
        $minimumRequired = $keuzedeel->minimum_studenten ?? 1;
        
        return $currentEnrollment >= $minimumRequired;
    }

    private function assignStudent($student, $keuzedeel)
    {
        // Create actual enrollment
        \App\Models\Inschrijving::create([
            'id' => Str::uuid(),
            'student_id' => $student->id,
            'keuzedeel_id' => $keuzedeel->id,
            'status' => 'confirmed',
        ]);
    }

    private function rejectOtherChoices($studentId, $assignedPriority)
    {
        MoreOption::where('student_id', $studentId)
            ->where('priority', '!=', $assignedPriority)
            ->update(['status' => 'rejected']);
    }
}
