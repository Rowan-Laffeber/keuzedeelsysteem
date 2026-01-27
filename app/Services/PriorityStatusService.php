<?php

namespace App\Services;

use App\Models\Keuzedeel;
use App\Models\Inschrijving;
use Illuminate\Support\Facades\DB;

class PriorityStatusService
{
    public static function recalc(?Keuzedeel $keuzedeel = null, ?string $studentId = null)
    {
        $query = Inschrijving::with('keuzedeel');

        if ($keuzedeel) {
            $ids = $keuzedeel->parent_id
                ? [$keuzedeel->id]
                : array_merge([$keuzedeel->id], $keuzedeel->delen()->pluck('id')->toArray());
            $query->whereIn('keuzedeel_id', $ids);
        }

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $inschrijvingen = $query->get()->groupBy('student_id');

        DB::transaction(function () use ($inschrijvingen) {
            foreach ($inschrijvingen as $studentId => $studentInschrijvingen) {

                // Step 1: reset all statuses based on active/inactive
                foreach ($studentInschrijvingen as $i) {
                    $i->status = $i->keuzedeel->actief ? 'ingediend' : 'afgewezen';
                    $i->save();
                }

                // Step 2: process priorities in order
                $approvedThisStudent = false;

                foreach ([1, 2, 3] as $priority) {
                    $prioInschrijvingen = $studentInschrijvingen->where('priority', $priority);

                    foreach ($prioInschrijvingen as $i) {
                        $deel = $i->keuzedeel;

                        // Skip inactive keuzedeel
                        if (!$deel->actief) {
                            $i->status = 'afgewezen';
                            $i->save();
                            continue;
                        }

                        // Check maximum
                        $currentApproved = Inschrijving::where('keuzedeel_id', $deel->id)
                            ->where('status', 'goedgekeurd')
                            ->count();

                        if ($deel->maximum_studenten !== null && $currentApproved >= $deel->maximum_studenten) {
                            $i->status = 'afgewezen';
                            $i->save();
                            continue;
                        }

                        // Check minimum: only reject if enough students already exist
                        $totalEnrollments = Inschrijving::where('keuzedeel_id', $deel->id)
                            ->whereIn('status', ['goedgekeurd', 'aangemeld'])
                            ->count();

                        if ($deel->minimum_studenten && $currentApproved < $deel->minimum_studenten && $totalEnrollments >= $deel->minimum_studenten) {
                            $i->status = 'afgewezen';
                            $i->save();
                            continue;
                        }

                        // Approve if priority 1 or if no higher priority approved yet
                        if ($priority === 1 || !$approvedThisStudent) {
                            $i->status = 'goedgekeurd';
                            $i->save();
                            $approvedThisStudent = true;
                        } else {
                            // Lower priority, keep as aangemeld if higher approved
                            $i->status = 'aangemeld';
                            $i->save();
                        }
                    }
                }
            }
        });
    }
}
