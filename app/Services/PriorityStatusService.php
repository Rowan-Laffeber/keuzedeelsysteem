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

                // Reset statuses based on actief
                foreach ($studentInschrijvingen as $inschrijving) {
                    $inschrijving->status = $inschrijving->keuzedeel->actief ? 'ingediend' : 'afgewezen';
                    $inschrijving->save();
                }

                $approvedThisStudent = false;

                foreach ([1, 2, 3] as $priority) {
                    $prioInschrijvingen = $studentInschrijvingen->where('priority', $priority);

                    foreach ($prioInschrijvingen as $inschrijving) {
                        $deel = $inschrijving->keuzedeel;

                        if (!$deel->actief) {
                            $inschrijving->status = 'afgewezen';
                            $inschrijving->save();
                            continue;
                        }

                        // Determine relevant keuzedeel IDs (for parent grouping)
                        if ($deel->max_type_parent === 'parent' && $deel->parent_id) {
                            $parentId = $deel->parent_id;
                            $relatedIds = Keuzedeel::where('parent_id', $parentId)->pluck('id')->toArray();
                            $relatedIds[] = $parentId;
                        } else {
                            $relatedIds = [$deel->id];
                        }

                        // ✅ Check if max approved reached for this keuzedeel/group
                        $currentApproved = Inschrijving::whereIn('keuzedeel_id', $relatedIds)
                            ->where('status', 'goedgekeurd')
                            ->count();

                        if ($deel->maximum_studenten !== null && $currentApproved >= $deel->maximum_studenten) {
                            $inschrijving->status = 'ingediend'; // keep waiting
                            $inschrijving->save();
                            continue;
                        }

                        // Approve if priority 1 or no higher priority approved
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
    }
}
