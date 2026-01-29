<?php

namespace App\Http\Controllers;

use App\Models\Keuzedeel;
use App\Models\Inschrijving;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\PriorityStatusService;

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

        $activeCount = $student
            ? $student->inschrijvingen()
                ->whereIn('status', ['goedgekeurd', 'ingediend'])
                ->count()
            : 0;

        $deelInfo = $delen->map(function ($deel) use ($student, $now, $activeCount) {
            $isIngeschreven = $student
                ? $student->inschrijvingen()
                    ->where('keuzedeel_id', $deel->id)
                    ->whereIn('status', ['goedgekeurd', 'ingediend'])
                    ->exists()
                : false;

            $start = Carbon::parse($deel->start_inschrijving);
            $end   = Carbon::parse($deel->eind_inschrijving);
            $inPeriod = $now->between($start, $end);

            // Max per priority (just info)
            $maxReachedByPrio = [];
            for ($prio = 1; $prio <= 3; $prio++) {
                $count = Inschrijving::where('keuzedeel_id', $deel->id)
                    ->where('priority', $prio)
                    ->where('status', 'goedgekeurd')
                    ->count();
                $maxReachedByPrio[$prio] = $deel->maximum_studenten !== null && $count >= $deel->maximum_studenten;
            }

            $canEnroll = $student && !$isIngeschreven && $activeCount < 3 && $inPeriod && $deel->actief;

            $availablePriorities = [];
            if ($canEnroll) {
                foreach ($maxReachedByPrio as $prio => $full) {
                    if (!$full) $availablePriorities[] = $prio;
                }
                if (empty($availablePriorities)) $canEnroll = false;
            }

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
                    'max_reached_by_prio' => $maxReachedByPrio,
                    'available_priorities' => $availablePriorities,
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

        $student = $user->student;
        $keuzedeelId = $request->input('keuzedeel_id');
        $priority = (int) $request->input('priority');
        $opmerkingen = $request->input('opmerkingen');

        $keuzedeel = Keuzedeel::findOrFail($keuzedeelId);
        $now = Carbon::now();

        // Validate active and enrollment period
        if (!$keuzedeel->actief) return back()->with('error', 'Dit keuzedeel is niet actief.');
        $start = Carbon::parse($keuzedeel->start_inschrijving);
        $end   = Carbon::parse($keuzedeel->eind_inschrijving);
        if (!$now->between($start, $end)) return back()->with('error', 'De inschrijvingsperiode is gesloten.');

        // Check if student already has max 3 active enrollments
        $activeEnrollments = $student->inschrijvingen()
            ->whereIn('status', ['goedgekeurd', 'ingediend'])
            ->get();

        if ($activeEnrollments->count() >= 3) return back()->with('error', 'Je hebt al 3 inschrijvingen.');

        // Check if student already has this priority
        if ($activeEnrollments->where('priority', $priority)->count() > 0) {
            return back()->with('error', "Je hebt al een inschrijving met prioriteit $priority.");
        }

        // Check if student already enrolled in this keuzedeel
        if ($student->inschrijvingen()->where('keuzedeel_id', $keuzedeelId)->exists()) {
            return back()->with('error', 'Je bent al ingeschreven voor dit keuzedeel.');
        }

        // Determine relevant keuzedeel IDs based on max_type_parent
        if ($keuzedeel->max_type_parent === 'parent' && $keuzedeel->parent_id) {
            $parentId = $keuzedeel->parent_id;
            $relatedIds = Keuzedeel::where('parent_id', $parentId)->pluck('id')->toArray();
            $relatedIds[] = $parentId;
        } else {
            $relatedIds = [$keuzedeel->id];
            }

        // Check per-priority cap
        $prioCount = Inschrijving::whereIn('keuzedeel_id', $relatedIds)
            ->where('priority', $priority)
            ->whereIn('status', ['goedgekeurd', 'ingediend'])
            ->count();

        if ($keuzedeel->maximum_studenten !== null && $prioCount >= $keuzedeel->maximum_studenten) {
            return back()->with('error', "Deze prioriteit is vol. Kies een andere prioriteit of keuzedeel.");
        }

        // Set initial status based on priority
        $initialStatus = match($priority) {
            1 => 'goedgekeurd',  // Priority 1 is immediately approved if available
            2, 3 => 'ingediend',
            default => 'ingediend'
        };

        Inschrijving::create([
            'id' => Str::uuid(),
            'student_id' => $student->id,
            'keuzedeel_id' => $keuzedeelId,
            'priority' => $priority,
            'opmerkingen' => $opmerkingen,
            'status' => $initialStatus,
            'inschrijfdatum' => $now,
        ]);

        // Recalculate statuses for this student
        PriorityStatusService::recalc(studentId: $student->id);

        return back()->with('success', 'Succesvol ingeschreven!');
    }


    /**
     * Delete an enrollment
     */
    
     /**
 * Delete an enrollment
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

        // Block deletion if status is 'afgerond'
        if ($inschrijving->status === 'afgerond') {
            return back()->with('error', 'Je kunt deze inschrijving niet verwijderen omdat de status afgerond is.');
        }

        $inschrijving->delete();

        // Recalculate statuses for affected students (only for this keuzedeel)
        PriorityStatusService::recalc(Keuzedeel::find($keuzedeelId));

        return back()->with('success', 'Succesvol uitgeschreven!');
    }

     

}
