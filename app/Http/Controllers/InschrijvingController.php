<?php

namespace App\Http\Controllers;
use App\Models\Keuzedeel;
use App\Models\Inschrijving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InschrijvingController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'student') {
            return redirect()->route('home')->with('error', 'Alleen studenten mogen deze pagina bekijken.');
        }

        $keuzedeelId = $request->input('keuzedeel_id');
        $student = auth()->user()->student;
        $keuzedeel = Keuzedeel::findOrFail($keuzedeelId);

        // Check if student has made all 3 choices first
        $studentChoices = $student->inschrijvingen()
            ->where('status', 'pending')
            ->whereNotNull('priority')
            ->count();
        
        // Debug logging
        \Log::info('Student ID: ' . $student->id);
        \Log::info('Student choices count: ' . $studentChoices);
        \Log::info('All student inschrijvingen: ' . $student->inschrijvingen()->count());
        
        if ($studentChoices < 3) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'needs_choices',
                    'message' => 'Je moet eerst 3 keuzes opgeven (1e, 2e, en 3e keuze) voordat je je kunt inschrijven. (Current: ' . $studentChoices . '/3)'
                ]);
            }
            return redirect()->route('more-options.index')
                ->with('error', 'Je moet eerst 3 keuzes opgeven (1e, 2e, en 3e keuze) voordat je je kunt inschrijven. (Current: ' . $studentChoices . '/3)');
        }

        // Check if already enrolled
        $existingInschrijving = $student->inschrijvingen()
            ->where('keuzedeel_id', $keuzedeelId)
            ->first();

        if ($existingInschrijving && $existingInschrijving->status !== 'cancelled') {
            return back()->with('error', 'Je bent al ingeschreven.');
        }

        // Check if keuzedeel is full
        if ($keuzedeel->bevestigdeStudenten()->count() >= $keuzedeel->maximum_studenten) {
            return back()->with('error', 'Maximum aantal inschrijvingen bereikt.');
        }

        // Create new enrollment or reactivate cancelled one
        if ($existingInschrijving && $existingInschrijving->status === 'cancelled') {
            // Reactivate cancelled enrollment
            $existingInschrijving->status = 'confirmed';
            $existingInschrijving->inschrijfdatum = now();
            $existingInschrijving->save();
        } else {
            // Create new enrollment record
            Inschrijving::create([
                'id' => Str::uuid(),
                'student_id' => $student->id,
                'keuzedeel_id' => $keuzedeelId,
                'status' => 'confirmed',
            ]);
        }

        // Return JSON response for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Succesvol ingeschreven! Bekijk je profiel voor al je ingeschreven keuzedelen.',
                'redirect' => route('home')
            ]);
        }

        return back()->with('success', 'Succesvol ingeschreven! Bekijk je profiel voor al je ingeschreven keuzedelen.');
    }

    public function destroy(Request $request)
    {
        $keuzedeelId = $request->input('keuzedeel_id');
        $student = auth()->user()->student;
        
        \Log::info('Uitschrijven attempt - Student ID: ' . $student->id . ', Keuzedeel ID: ' . $keuzedeelId);
        
        $inschrijving = $student->inschrijvingen()
            ->where('keuzedeel_id', $keuzedeelId)
            ->whereIn('status', ['confirmed', 'pending'])  // Look for both confirmed and pending
            ->first();

        \Log::info('Found inschrijving: ' . ($inschrijving ? 'Yes' : 'No'));
        if ($inschrijving) {
            \Log::info('Inschrijving status: ' . $inschrijving->status);
        }

        if (!$inschrijving) {
            \Log::info('No confirmed inschrijving found, checking all statuses');
            $allInschrijvingen = $student->inschrijvingen()
                ->where('keuzedeel_id', $keuzedeelId)
                ->get();
            \Log::info('All inschrijvingen for this keuzedeel: ' . $allInschrijvingen->count());
            foreach ($allInschrijvingen as $insch) {
                \Log::info('Status: ' . $insch->status);
            }
            
            return back()->with('error', 'Geen inschrijving gevonden om uit te schrijven.');
        }

        $inschrijving->cancel();

        // Clear any cached counts
        $keuzedeel = $inschrijving->keuzedeel;
        $keuzedeel->refreshEnrollmentCount();

        \Log::info('Successfully cancelled inschrijving');

        return back()->with('success', 'Succesvol uitgeschreven!');
    }

    // MoreOptions methods
    public function moreOptionsIndex()
    {
        $user = Auth::user();
        
        if ($user->role !== 'student') {
            return redirect()->route('home')->with('error', 'Alleen studenten mogen keuzes opgeven.');
        }

        $student = $user->student;
        
        // Get student's current choices
        $choices = $student->inschrijvingen()
            ->where('status', 'pending')
            ->whereNotNull('priority')
            ->with('keuzedeel')
            ->orderBy('priority')
            ->get()
            ->keyBy('priority');

        // Get all available keuzedelen for dropdowns (child keuzedelen only)
        $availableKeuzedelen = Keuzedeel::where('is_open', true)
            ->whereNotNull('parent_id')  // Only get child keuzedelen
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

    public function moreOptionsStore(Request $request)
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
        $student->inschrijvingen()
            ->where('status', 'pending')
            ->whereNotNull('priority')
            ->delete();

        // Create new choices
        $choices = [
            1 => $request->input('first_choice'),
            2 => $request->input('second_choice'),
            3 => $request->input('third_choice'),
        ];

        \Log::info('Creating choices for student: ' . $student->id);
        \Log::info('Choices data: ' . json_encode($choices));

        foreach ($choices as $priority => $keuzedeelId) {
            $inschrijving = Inschrijving::create([
                'id' => Str::uuid(),
                'student_id' => $student->id,
                'keuzedeel_id' => $keuzedeelId,
                'status' => 'pending',
                'priority' => $priority,
            ]);
            \Log::info('Created inschrijving: ' . $inschrijving->id . ' with priority ' . $priority);
        }

        // Return JSON response for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Je keuzes zijn succesvol opgeslagen!',
                'redirect' => route('home')
            ]);
        }

        return redirect()->route('more-options.index')
            ->with('success', 'Je keuzes zijn succesvol opgeslagen!');
    }

    public function processChoices()
    {
        // This method would be called by an admin or scheduled job
        // to process all pending choices and assign students
        
        $pendingChoices = Inschrijving::with(['student', 'keuzedeel'])
            ->pending()
            ->withPriority()
            ->orderBy('priority')
            ->get()
            ->groupBy('student_id');

        foreach ($pendingChoices as $studentId => $choices) {
            // Only process if student has made all 3 choices
            if ($choices->count() === 3) {
                $this->assignStudentToBestChoice($choices);
            }
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
                $choice->status = 'confirmed';
                $choice->save();
                
                // Reject other choices for this student
                $this->rejectOtherChoices($choice->student_id, $choice->priority);
                return;
            }
        }
        
        // If no choice meets requirements, mark all as rejected
        foreach ($choices as $choice) {
            $choice->status = 'cancelled';
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
        // The enrollment record already exists, just confirm it
        $inschrijving = Inschrijving::where('student_id', $student->id)
            ->where('keuzedeel_id', $keuzedeel->id)
            ->first();
        
        if ($inschrijving) {
            $inschrijving->status = 'confirmed';
            $inschrijving->inschrijfdatum = now();
            $inschrijving->save();
        }
    }

    private function rejectOtherChoices($studentId, $assignedPriority)
    {
        Inschrijving::where('student_id', $studentId)
            ->withPriority()
            ->where('priority', '!=', $assignedPriority)
            ->update(['status' => 'cancelled']);
    }
}
