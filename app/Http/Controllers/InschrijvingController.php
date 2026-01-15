<?php

namespace App\Http\Controllers;
use App\Models\Keuzedeel;
use Illuminate\Http\Request;


class InschrijvingController extends Controller
{
    public function inschrijven($keuzedeelId)
    {
        $student = auth()->user();
        $keuzedeel = Keuzedeel::findOrFail($keuzedeelId);

     
        if ($student->keuzedelen()->where('keuzedeel_id', $keuzedeelId)->exists()) {
            return back()->with('error', 'Je bent al ingeschreven.');
        }

        
        if ($keuzedeel->students()->count() >= $keuzedeel->max_inschrijvingen) {
            return back()->with('error', 'Maximum aantal inschrijvingen bereikt.');
        }

        
        $student->keuzedelen()->attach($keuzedeelId);

        return back()->with('success', 'Succesvol ingeschreven!');
    }

}
