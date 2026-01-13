<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Keuzedeel;
use App\Models\Inschrijving;

class InschrijvingController extends Controller
{
    public function store(Request $request)
    {
        /* 1. Validate input */
        $request->validate([
            'keuzedeel_id' => 'required|uuid|exists:keuzedelen,id',
            'keuze' => 'required|in:1,2',
        ]);

        /* 2. Logged in student */
        $student = auth()->user()->student;
        if (!$student) {
            return response()->json(['message' => 'Geen student'], 403);
        }

        /* 3. Keuzedeel */
        $keuzedeel = Keuzedeel::findOrFail($request->keuzedeel_id);

        /* 4. Must be Deel 1 or Deel 2 */
        if ($keuzedeel->parent_id === null) {
            return response()->json(['message' => 'Ongeldig keuzedeel'], 422);
        }

        /* 5. Active & open */
        if (
            !$keuzedeel->actief ||
            !$keuzedeel->is_open ||
            now()->lt($keuzedeel->start_inschrijving) ||
            now()->gt($keuzedeel->eind_inschrijving)
        ) {
            return response()->json(['message' => 'Inschrijving gesloten'], 422);
        }

        /* 6. Max studenten */
        if ($keuzedeel->maximum_studenten > 0) {
            $count = Inschrijving::where(function ($q) use ($keuzedeel) {
                $q->where('eerste_keuze_keuzedeel_id', $keuzedeel->id)
                  ->orWhere('tweede_keuze_keuzedeel_id', $keuzedeel->id)
                  ->orWhere('toegewezen_keuzedeel_id', $keuzedeel->id);
            })->count();

            if ($count >= $keuzedeel->maximum_studenten) {
                return response()->json(['message' => 'Keuzedeel zit vol'], 422);
            }
        }

        /* 7. Already afgerond */
        $afgerond = Inschrijving::where('student_id', $student->id)
            ->where('afgerond', true)
            ->where('toegewezen_keuzedeel_id', $keuzedeel->id)
            ->exists();

        if ($afgerond) {
            return response()->json(['message' => 'Al afgerond'], 422);
        }

        /* 8. One inschrijving per parent keuzedeel */
        $inschrijving = Inschrijving::where('student_id', $student->id)
            ->whereHas('eersteKeuze', function ($q) use ($keuzedeel) {
                $q->where('parent_id', $keuzedeel->parent_id);
            })
            ->first();

        if (!$inschrijving) {
            $inschrijving = Inschrijving::create([
                'id' => (string) Str::uuid(),
                'student_id' => $student->id,
            ]);
        }

        /* 9. Save choice */
        if ($request->keuze == 1) {
            $inschrijving->eerste_keuze_keuzedeel_id = $keuzedeel->id;
        } else {
            $inschrijving->tweede_keuze_keuzedeel_id = $keuzedeel->id;
        }

        $inschrijving->save();

        return response()->json(['success' => true]);
    }
}
