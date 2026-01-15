<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Keuzedeel;
use App\Models\Inschrijving;

class HomeController extends Controller
{
    public function home()
    {
        $parents = Keuzedeel::whereNull('parent_id')
            ->orderBy('volgorde')
            ->get();

        return view('home', compact('parents'));
    }

    public function info(Keuzedeel $keuzedeel)
    {
        $delen = $keuzedeel->delen()->orderBy('volgorde')->get();

        // --- Check if logged-in student is already inschreven ---
        $student = Auth::user()?->student;
        $studentInschrijving = null;

        // if ($student) {
        //     $studentInschrijving = Inschrijving::where('student_id', $student->id)
        //         ->where(function ($q) use ($keuzedeel) {
        //             $q->where('eerste_keuze_keuzedeel_id', $keuzedeel->id)
        //               ->orWhere('tweede_keuze_keuzedeel_id', $keuzedeel->id);
        //         })->first();
        // }

        return view('info', compact('keuzedeel', 'delen', 'studentInschrijving'));
    }
}
