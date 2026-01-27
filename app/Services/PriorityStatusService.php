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

                // Reset all statuses
                foreach ($studentInschrijvingen as $i) {
                    $i->status = $i->keuzedeel->actief ? 'ingediend' : 'afgewezen';
                    $i->save();
                }

                // Approve first eligible inschrijving in priority order
                $approved = false;

                foreach ([1, 2, 3] as $prio) {
                    $prioInschrijvingen = $studentInschrijvingen->where('priority', $prio);

                    foreach ($prioInschrijvingen as $i) {
                        $deel = $i->keuzedeel;

                        // Skip if inactive
                        if (!$deel->actief) continue;

                        // Skip if max reached
                        $currentApproved = Inschrijving::where('keuzedeel_id', $deel->id)
                            ->where('status', 'goedgekeurd')
                            ->count();

                        if ($deel->maximum_studenten !== null && $currentApproved >= $deel->maximum_studenten) {
                            continue;
                        }

                        // Approve first eligible
                        if (!$approved) {
                            $i->status = 'goedgekeurd';
                            $i->save();
                            $approved = true;
                        }
                    }
                }

            }
        });
    }
}
