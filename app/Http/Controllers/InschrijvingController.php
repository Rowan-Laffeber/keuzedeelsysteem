<?php

namespace App\Http\Controllers;
use App\Models\Keuzedeel;
use App\Models\Inschrijving;
use Illuminate\Http\Request;
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

        // Check if already enrolled
        $existingInschrijving = $student->inschrijvingen()
            ->where('keuzedeel_id', $keuzedeelId)
            ->first();

        if ($existingInschrijving && $existingInschrijving->status !== 'afgewezen') {
            return back()->with('error', 'Je bent al ingeschreven.');
        }

        // Check if keuzedeel is full
        if ($keuzedeel->bevestigdeStudenten()->count() >= $keuzedeel->maximum_studenten) {
            return back()->with('error', 'Maximum aantal inschrijvingen bereikt.');
        }

        // Create new enrollment or reactivate cancelled one
        if ($existingInschrijving && $existingInschrijving->status === 'afgewezen') {
            // Reactivate cancelled enrollment
            $existingInschrijving->status = 'goedgekeurd';
            $existingInschrijving->inschrijfdatum = now();
            $existingInschrijving->save();
        } else {
            // Create new enrollment record
            Inschrijving::create([
                'id' => Str::uuid(),
                'student_id' => $student->id,
                'keuzedeel_id' => $keuzedeelId,
                'status' => 'goedgekeurd',
            ]);
        }

        return back()->with('success', 'Succesvol ingeschreven!');
    }

    public function destroy(Request $request)
    {
        $keuzedeelId = $request->input('keuzedeel_id');
        $student = auth()->user()->student;
        
        $inschrijving = $student->inschrijvingen()
            ->where('keuzedeel_id', $keuzedeelId)
            ->where('status', 'goedgekeurd')
            ->first();

        if (!$inschrijving) {
            return back()->with('error', 'Geen actieve inschrijving gevonden.');
        }

        $inschrijving->cancel();

        // Clear any cached counts
        $keuzedeel = $inschrijving->keuzedeel;
        $keuzedeel->refreshEnrollmentCount();

        return back()->with('success', 'Succesvol uitgeschreven!');
    }
}
