<?php

namespace App\Services;

use App\Models\Keuzedeel;
use App\Models\Inschrijving;
use Illuminate\Support\Facades\DB;

class PriorityStatusService
{
    /**
     * Recalculate priorities and statuses for a student or a keuzedeel.
     * 'afgerond' status is considered final and will never be changed.
     */
    public static function recalc(?Keuzedeel $keuzedeel = null, ?string $studentId = null)
    {
        $query = Inschrijving::with('keuzedeel');

        // Filter by keuzedeel if provided
        if ($keuzedeel) {
            $ids = $keuzedeel->parent_id
                ? [$keuzedeel->id] // subdeel only
                : array_merge([$keuzedeel->id], $keuzedeel->delen()->pluck('id')->toArray()); // parent + subdelen
            $query->whereIn('keuzedeel_id', $ids);
        }

        // Filter by student if provided
        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $inschrijvingen = $query->get()->groupBy('student_id');

        DB::transaction(function () use ($inschrijvingen) {

            foreach ($inschrijvingen as $studentId => $studentInschrijvingen) {

                // --- 1. Reset statuses based on keuzedeel actief, but never overwrite 'afgerond' ---
                foreach ($studentInschrijvingen as $inschrijving) {
                    if ($inschrijving->status !== 'afgerond') {
                        $inschrijving->status = $inschrijving->keuzedeel->actief ? 'ingediend' : 'afgewezen';
                        $inschrijving->save();
                    }
                }

                $approvedThisStudent = false;

                // --- 2. Process priorities 1, 2, 3 ---
                foreach ([1, 2, 3] as $priority) {
                    $prioInschrijvingen = $studentInschrijvingen->where('priority', $priority);

                    foreach ($prioInschrijvingen as $inschrijving) {
                        $deel = $inschrijving->keuzedeel;

                        // Skip any status updates if 'afgerond'
                        if ($inschrijving->status === 'afgerond') {
                            continue;
                        }

                        // --- 2a. Check if keuzedeel is actief ---
                        if (!$deel->actief) {
                            $inschrijving->status = 'afgewezen';
                            $inschrijving->save();
                            continue;
                        }

                        // --- 2b. Determine relevant keuzedeel IDs (parent grouping) ---
                        if ($deel->max_type_parent === 'parent' && $deel->parent_id) {
                            $parentId = $deel->parent_id;
                            $relatedIds = Keuzedeel::where('parent_id', $parentId)->pluck('id')->toArray();
                            $relatedIds[] = $parentId;
                        } else {
                            $relatedIds = [$deel->id];
                        }

                        // --- 2c. Check if max approved reached ---
                        $currentApproved = Inschrijving::whereIn('keuzedeel_id', $relatedIds)
                            ->where('status', 'goedgekeurd')
                            ->count();

                        if ($deel->maximum_studenten !== null && $currentApproved >= $deel->maximum_studenten) {
                            $inschrijving->status = 'ingediend'; // keep waiting
                            $inschrijving->save();
                            continue;
                        }

                        // --- 2d. Approve based on priority ---
                        if ($priority === 1 || !$approvedThisStudent) {
                            $inschrijving->status = 'goedgekeurd';
                            $approvedThisStudent = true;
                        } else {
                            $inschrijving->status = 'ingediend';
                        }

                        $inschrijving->save();
                    }
                }
            }
        });
        // --- 3. Promote students to highest possible priority (single pass, safe) ---
        $students = Inschrijving::with('keuzedeel')
        ->whereNotIn('status', ['afgewezen', 'afgerond'])
        ->get()
        ->groupBy('student_id');

        DB::transaction(function () use ($students) {

        foreach ($students as $studentInschrijvingen) {

            // huidige goedgekeurde (max 1)
            $currentApproved = $studentInschrijvingen
                ->where('status', 'goedgekeurd')
                ->sortBy('priority')
                ->first();

            // mogelijke kandidaten (ingediend, actief, plek)
            $candidates = $studentInschrijvingen
                ->where('status', 'ingediend')
                ->sortBy('priority');

            foreach ($candidates as $candidate) {

                $deel = $candidate->keuzedeel;

                // check actief
                if (!$deel->actief) {
                    continue;
                }

                // bepaal relevante IDs (parent-logica)
                if ($deel->parent_max_type === 'parent' && $deel->parent_id) {
                    $relatedIds = Keuzedeel::where('parent_id', $deel->parent_id)
                        ->pluck('id')
                        ->push($deel->parent_id)
                        ->toArray();
                } else {
                    $relatedIds = [$deel->id];
                }

                // check plek
                $approvedCount = Inschrijving::whereIn('keuzedeel_id', $relatedIds)
                    ->where('status', 'goedgekeurd')
                    ->count();

                if ($deel->maximum_studenten !== null && $approvedCount >= $deel->maximum_studenten) {
                    continue;
                }

                // geen huidige goedgekeurde → promote
                if (!$currentApproved) {
                    $candidate->status = 'goedgekeurd';
                    $candidate->save();
                    break;
                }

                // kandidaat heeft hogere prioriteit (lager getal)
                if ($candidate->priority < $currentApproved->priority) {
                    $currentApproved->status = 'ingediend';
                    $currentApproved->save();

                    $candidate->status = 'goedgekeurd';
                    $candidate->save();
                }

                break; // max 1 promotie per student
            }
        }
    });

    }
}
