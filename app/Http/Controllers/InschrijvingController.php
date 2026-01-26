<?php

namespace App\Http\Controllers;

use App\Models\Keuzedeel;
use App\Models\Inschrijving;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InschrijvingController extends Controller
{
    /**
     * Show keuzedeel info with all its delen
     */
    public function show($keuzedeelId)
    {
        $user = auth()->user();
        $student = $user->student ?? null;

        $keuzedeel = Keuzedeel::with('delen')->findOrFail($keuzedeelId);
        $delen = $keuzedeel->delen()->orderBy('volgorde')->get();
        $now = Carbon::now();

        // Compute active enrollments once
        $activeCount = $student
            ? $student->inschrijvingen()
                ->whereIn('status', ['confirmed', 'pending'])
                ->count()
            : 0;

        // Prepare info for each deel
        $deelInfo = $delen->map(function ($deel) use ($student, $now, $activeCount) {
            $isIngeschreven = $student
                ? $student->inschrijvingen()
                    ->where('keuzedeel_id', $deel->id)
                    ->whereIn('status', ['confirmed','pending'])
                    ->exists()
                : false;

            $start = Carbon::parse($deel->start_inschrijving);
            $end   = Carbon::parse($deel->eind_inschrijving);
            $inPeriod = $now->between($start, $end);

            // Determine max reached
            if ($deel->maximum_studenten !== null) {
                // Max-type subdeel → enforce own max
                $maxReached = $deel->bevestigdeStudenten()->count() >= $deel->maximum_studenten;
            } else {
                // Max-type parent → sum across all siblings
                $siblingIds = Keuzedeel::where('parent_id', $deel->parent_id)->pluck('id');
                $totalBevestigde = \App\Models\Inschrijving::whereIn('keuzedeel_id', $siblingIds)
                    ->where('status', 'confirmed')
                    ->count();
                $parent = Keuzedeel::find($deel->parent_id);
                $maxReached = $parent && $parent->maximum_studenten !== null
                    ? $totalBevestigde >= $parent->maximum_studenten
                    : false;
            }

            $canEnroll = $student && !$isIngeschreven && $activeCount < 3 && $inPeriod && !$maxReached && $deel->actief;

            return [
                'id' => $deel->id,
                'title' => $deel->title,
                'description' => $deel->description,
                'actief' => $deel->actief,
                'ingeschreven_count' => $deel->bevestigdeStudenten()->count(),
                'start_inschrijving' => $start->format('d-m-Y'),
                'eind_inschrijving' => $end->format('d-m-Y'),
                'is_ingeschreven' => $isIngeschreven,
                'in_period' => $inPeriod,
                'max_reached' => $maxReached,
                'can_enroll' => $canEnroll,
            ];
        });

        return view('info', [
            'keuzedeel' => $keuzedeel,
            'delen' => $deelInfo,
            'activeCount' => $activeCount,
        ]);
    }

    /**
     * Store a new enrollment
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'student') {
            return redirect()->route('home')->with('error', 'Alleen studenten mogen deze pagina bekijken.');
        }

        $keuzedeelId = $request->input('keuzedeel_id');
        $priority = $request->input('priority');
        $opmerkingen = $request->input('opmerkingen');

        $student = $user->student;
        $keuzedeel = Keuzedeel::findOrFail($keuzedeelId);
        $now = Carbon::now();

        // Check if keuzedeel is active
        if (!$keuzedeel->actief) {
            return back()->with('error', 'Dit keuzedeel is niet actief.');
        }

        // Check enrollment period
        $start = Carbon::parse($keuzedeel->start_inschrijving);
        $end   = Carbon::parse($keuzedeel->eind_inschrijving);
        if (!$now->between($start, $end)) {
            return back()->with('error', 'De inschrijvingsperiode is gesloten.');
        }

        // Max 3 active enrollments
        $activeEnrollments = $student->inschrijvingen()
            ->whereIn('status', ['confirmed', 'pending'])
            ->get();

        if ($activeEnrollments->count() >= 3) {
            return back()->with('error', 'Je hebt al 3 inschrijvingen. Je kunt geen extra inschrijving doen.');
        }

        // Priority check (only confirmed/pending)
        if ($activeEnrollments->where('priority', $priority)->count() > 0) {
            return back()->with('error', "Je hebt al een inschrijving met prioriteit $priority.");
        }

        // Already enrolled check
        $existing = $student->inschrijvingen()
            ->where('keuzedeel_id', $keuzedeelId)
            ->whereIn('status', ['confirmed', 'pending'])
            ->first();

        if ($existing) {
            return back()->with('error', 'Je bent al ingeschreven voor dit keuzedeel.');
        }

        // --- MAX STUDENTS LOGIC ---
        if ($keuzedeel->maximum_studenten !== null) {
            // Max-type subdeel → enforce own max
            $maxStudents = $keuzedeel->maximum_studenten;
            $totalBevestigde = $keuzedeel->bevestigdeStudenten()->count();
        } else {
            // Max-type parent → sum across all siblings
            $parent = Keuzedeel::find($keuzedeel->parent_id);

            if (!$parent || $parent->maximum_studenten === null) {
                return back()->with('error', 'Maximum aantal inschrijvingen voor dit keuzedeel is nog niet ingesteld.');
            }

            $maxStudents = $parent->maximum_studenten;
            $siblingIds = Keuzedeel::where('parent_id', $keuzedeel->parent_id)->pluck('id');
            $totalBevestigde = Inschrijving::whereIn('keuzedeel_id', $siblingIds)
                ->where('status', 'confirmed')
                ->count();
        }

        if ($maxStudents !== null && $totalBevestigde >= $maxStudents) {
            return back()->with('error', 'Maximum aantal inschrijvingen bereikt voor dit keuzedeel.');
        }

        // Create enrollment
        Inschrijving::create([
            'id' => Str::uuid(),
            'student_id' => $student->id,
            'keuzedeel_id' => $keuzedeelId,
            'status' => 'confirmed',
            'priority' => $priority,
            'opmerkingen' => $opmerkingen,
            'inschrijfdatum' => $now,
        ]);

        return back()->with('success', 'Succesvol ingeschreven!');
    }

    /**
     * Hard delete an enrollment
     */
    public function destroy(Request $request)
    {
        $student = auth()->user()->student;
        $keuzedeelId = $request->input('keuzedeel_id');

        $inschrijving = $student->inschrijvingen()
            ->where('keuzedeel_id', $keuzedeelId)
            ->first();

        if (!$inschrijving) {
            return back()->with('error', 'Geen inschrijving gevonden om te verwijderen.');
        }

        $inschrijving->delete();

        return back()->with('success', 'Inschrijving verwijderd.');
    }
}
