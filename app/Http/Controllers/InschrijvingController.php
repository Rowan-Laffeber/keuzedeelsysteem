<?php

namespace App\Http\Controllers;
use App\Models\Keuzedeel;
use Illuminate\Http\Request;


class InschrijvingController extends Controller
{
    public function store(Request $request)
    {
        $keuzedeelId = $request->input('keuzedeel_id');
        $student = auth()->user()->student;
        $keuzedeel = Keuzedeel::findOrFail($keuzedeelId);

        if ($student->keuzedelen()->where('keuzedeel_id', $keuzedeelId)->exists()) {
            return back()->with('error', 'Je bent al ingeschreven.');
        }

        if ($keuzedeel->students()->count() >= $keuzedeel->maximum_studenten) {
            return back()->with('error', 'Maximum aantal inschrijvingen bereikt.');
        }

        $student->keuzedelen()->attach($keuzedeelId);

        return back()->with('success', 'Succesvol ingeschreven!');
    }

}
