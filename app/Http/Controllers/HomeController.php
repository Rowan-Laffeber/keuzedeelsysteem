<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Keuzedeel;
use App\Models\Inschrijving;

class HomeController extends Controller
{
    public function home()
    {
        $user = Auth::user();

        // Niet-studenten zien alles
        if ($user->role !== 'student') {
            $parents = Keuzedeel::whereNull('parent_id')
                ->orderBy('volgorde')
                ->get();

            return view('home', compact('parents'));
        }

        // Student: bepaal opleidingsprefix (bijv. 25604 uit 25604BOL)
        $student = $user->student;
        preg_match('/^\d{5}/', $student->opleidingsnummer ?? '', $matches);
        $opleidingPrefix = $matches[0] ?? null;

        // ALLEEN parents met minstens één toegestaan child
        $parents = Keuzedeel::whereNull('parent_id')
            ->whereHas('delen', function ($query) use ($opleidingPrefix) {
                $query->forStudentOpleiding($opleidingPrefix);
            })
            ->orderBy('volgorde')
            ->get();

        return view('home', compact('parents'));
    }

    public function info(Keuzedeel $keuzedeel)
    {
        $user = Auth::user();

        // Niet-studenten zien alle children
        if ($user->role !== 'student') {
            $delen = $keuzedeel->delen()->orderBy('volgorde')->get();

            return view('info', compact('keuzedeel', 'delen'));
        }

        // Student: bepaal opleidingsprefix
        $student = $user->student;
        preg_match('/^\d{5}/', $student->opleidingsnummer ?? '', $matches);
        $opleidingPrefix = $matches[0] ?? null;

        // alleen toegestane children
        $delen = $keuzedeel->delen()
            ->forStudentOpleiding($opleidingPrefix)
            ->orderBy('volgorde')
            ->get();

        // Inschrijving check
        $studentInschrijving = null;

        return view('info', compact('keuzedeel', 'delen', 'studentInschrijving'));
    }
}
