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
        
        // CODE VOOR FILTEREN GEBASEERD OP OPLEIDINGSCODE, NIET NODIG
        // // Student: bepaal opleidingsprefix (bijv. 25604 uit 25604BOL)
        $student = $user->student;
        // preg_match('/^\d{5}/', $student->opleidingsnummer ?? '', $matches);
        // $opleidingPrefix = $matches[0] ?? null;

        // // ALLEEN parents met minstens één toegestaan child
        $parents = Keuzedeel::whereNull('parent_id')
            // ->whereHas('delen', function ($query) use ($opleidingPrefix){
            //     $query->forStudentOpleiding($opleidingPrefix);
            // })
            ->orderBy('volgorde')
            ->get();

        return view('home', compact('parents'));
    }

    public function profile()
    {
        $user = Auth::user();
        
        if ($user->role !== 'student') {
            return redirect()->route('home')->with('error', 'Alleen studenten hebben een profiel pagina.');
        }

        $student = $user->student;
        $ingeschrevenKeuzedelen = $student->keuzedelen()->with('parent')->get();

        return view('profile', compact('student', 'ingeschrevenKeuzedelen'));
    }

    public function info(Keuzedeel $keuzedeel)
    {
        $user = Auth::user();

        // Niet-studenten zien alle children
        if ($user->role !== 'student') {
            $delen = $keuzedeel->delen()->orderBy('volgorde')->get();

            return view('info', compact('keuzedeel', 'delen'));
        }

        // CODE VOOR FILTEREN GEBASEERD OP OPLEIDINGSCODE, NIET NODIG
        // // Student: bepaal opleidingsprefix
        $student = $user->student;
        // preg_match('/^\d{5}/', $student->opleidingsnummer ?? '', $matches);
        // $opleidingPrefix = $matches[0] ?? null;

        // // alleen toegestane children
        $delen = $keuzedeel->delen()
        //     ->forStudentOpleiding($opleidingPrefix)
            ->orderBy('volgorde')
            ->get();

        // Check enrollment status for each deel
        foreach ($delen as $deel) {
            $deel->is_ingeschreven = $student->keuzedelen()
                ->where('keuzedeel_id', $deel->id)
                ->exists();
        }

        return view('info', compact('keuzedeel', 'delen'));
    }
}
