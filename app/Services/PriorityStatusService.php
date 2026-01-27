<?php

namespace App\Services;

use App\Models\Keuzedeel;
use App\Models\Inschrijving;
use Illuminate\Support\Facades\DB;

class PriorityStatusService
{
    public static function recalc(?Keuzedeel $keuzedeel = null, ?string $studentId = null)
    {
        // Temporarily disabled to test enrollment logic
        return;
        
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

                // Sort by priority
                $sortedByPriority = $studentInschrijvingen->sortBy('priority');
                
                // Check each priority level
                foreach ([1, 2, 3] as $priority) {
                    $priorityInschrijvingen = $sortedByPriority->where('priority', $priority);

                    foreach ($priorityInschrijvingen as $inschrijving) {
                        $deel = $inschrijving->keuzedeel;

                        // Skip if inactive
                        if (!$deel->actief) {
                            $inschrijving->status = 'afgewezen';
                            $inschrijving->save();
                            continue;
                        }

                        // Check if minimum is reached for this keuzedeel
                        $currentApproved = Inschrijving::where('keuzedeel_id', $deel->id)
                            ->where('status', 'goedgekeurd')
                            ->count();

                        // Don't reject based on minimum if this is the only student or if priority is 1
                        $totalEnrollments = Inschrijving::where('keuzedeel_id', $deel->id)
                            ->whereIn('status', ['goedgekeurd', 'aangemeld'])
                            ->count();
                        
                        if ($deel->minimum_studenten && $currentApproved < $deel->minimum_studenten && $totalEnrollments >= $deel->minimum_studenten) {
                            $inschrijving->status = 'afgewezen';
                            $inschrijving->save();
                            continue;
                        }

                        // Check if maximum is reached
                        if ($deel->maximum_studenten !== null && $currentApproved >= $deel->maximum_studenten) {
                            $inschrijving->status = 'afgewezen';
                            $inschrijving->save();
                            continue;
                        }

                        // Set status based on priority and availability
                        if ($priority === 1) {
                            // Priority 1 is always approved if available
                            $inschrijving->status = 'goedgekeurd';
                            $inschrijving->save();
                        } else {
                            // Priority 2 & 3 are initially aangemeld, but check if higher priority was approved
                            $higherPriorityApproved = $studentInschrijvingen
                                ->where('priority', '<', $priority)
                                ->where('status', 'goedgekeurd')
                                ->count() > 0;

                            if ($higherPriorityApproved) {
                                // Higher priority is approved, keep this as aangemeld
                                $inschrijving->status = 'aangemeld';
                                $inschrijving->save();
                            } else {
                                // No higher priority approved, check if this can be approved
                                $hasApproved = $studentInschrijvingen
                                    ->where('status', 'goedgekeurd')
                                    ->count() > 0;

                                if (!$hasApproved) {
                                    // No approved enrollment yet, approve this one
                                    $inschrijving->status = 'goedgekeurd';
                                    $inschrijving->save();
                                } else {
                                    // Keep as aangemeld
                                    $inschrijving->status = 'aangemeld';
                                    $inschrijving->save();
                                }
                            }
                        }
                    }
                }
            }
        });
    }
}
