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

        // Add enrollment status to parents based on child enrollments
        if ($user->role === 'student') {
            $student = $user->student;
            
            foreach ($parents as $parent) {
                // Check if student is enrolled in any child of this parent
                $hasEnrollment = $student->inschrijvingen()
                    ->whereHas('keuzedeel', function($query) use ($parent) {
                        $query->where('parent_id', $parent->id);
                    })
                    ->where('status', '!=', 'afgewezen')
                    ->exists();
                
                $parent->is_ingeschreven = $hasEnrollment;
                
                // Get enrollment details for status display
                if ($hasEnrollment) {
                    $enrollment = $student->inschrijvingen()
                        ->whereHas('keuzedeel', function($query) use ($parent) {
                            $query->where('parent_id', $parent->id);
                        })
                        ->where('status', '!=', 'afgewezen')
                        ->with('keuzedeel')
                        ->orderBy('priority')
                        ->first();
                    
                    if ($enrollment) {
                        $parent->enrollment_status = $enrollment->status;
                        $parent->enrollment_priority = $enrollment->priority;
                        $parent->enrollment_keuzedeel = $enrollment->keuzedeel;
                    }
                }
            }
        }

        return view('home', compact('parents'));
    }

    public function profile()
    {
        $user = Auth::user();
        
        if ($user->role !== 'student') {
            return redirect()->route('home')->with('error', 'Alleen studenten hebben een profiel pagina.');
        }

        $student = $user->student;
        $ingeschrevenKeuzedelen = $student->bevestigdeKeuzedelen()->with('parent')->get();

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

        // Check if this keuzedeel has children (is a parent) or is a child itself
        if ($keuzedeel->delen()->count() > 0) {
            // This is a parent keuzedeel - show its children
            $delen = $keuzedeel->delen()
                // ->forStudentOpleiding($opleidingPrefix)
                ->orderBy('volgorde')
                ->get();
        } else {
            // This is a child keuzedeel - show it as the only deel
            $delen = collect([$keuzedeel]);
        }

        // Load enrollment counts and properties for each deel
        $delen->each(function ($deel) {
            $deel->load('bevestigdeStudenten');
            // Ensure individual properties are loaded
            $deel->refreshEnrollmentCount();
        });

        // Check enrollment status for each deel
        foreach ($delen as $deel) {
            $deel->is_ingeschreven = $student->inschrijvingen()
                ->where('keuzedeel_id', $deel->id)
                ->where('status', '!=', 'afgewezen')
                ->exists();
            
            // Refresh enrollment count to ensure fresh data
            $deel->refreshEnrollmentCount();
        }

        return view('info', compact('keuzedeel', 'delen'));
    }
}
