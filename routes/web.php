<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Models\Post;
use App\Models\Keuzedeel;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InschrijvingController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/', [HomeController::class, 'home'])->name('home');
    Route::get('/keuzedeel/{keuzedeel}', [HomeController::class, 'info'])->name('keuzedeel.info');
    Route::post('/inschrijven', [InschrijvingController::class, 'store'])->name('inschrijven.store');

    Route::get('/homeCOPYFORCONCEPT', function() {
        return view('homeCOPYFORCONCEPT');
    });

    Route::get('/newposttest', function() {
        $x = new Post();
        $x->title = "Mijn titel";
        $x->body = "Dit is de inhoud";
        $x->save();
        return "done";
    });

    // --- Keuzedeel aanmaken ---
    Route::get('/create', function() {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Alleen admins mogen deze pagina bekijken.');
        }

        // Alleen parent keuzedelen ophalen
        $parents = Keuzedeel::whereNull('parent_id')->get();

        // Keuzedeel nummers uit CSV, filter al bestaande
        $csvFile = base_path('tempfiles/Overzicht-keuzedeel-per-student.csv');
        $keuzedelen = [];
        if (file_exists($csvFile)) {
            $firstLine = file($csvFile)[0] ?? '';
            $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
            $handle = fopen($csvFile, 'r');
            $foundExaminerend = false;

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (in_array('Examinerend', $row)) {
                    $foundExaminerend = true;
                    continue;
                }
                if ($foundExaminerend) {
                    foreach ($row as $cell) {
                        $cell = trim($cell);
                        if (strpos($cell, 'K') !== false && !Keuzedeel::where('id', $cell)->exists()) {
                            $keuzedelen[] = $cell;
                        }
                    }
                    break;
                }
            }
            fclose($handle);
        }

        return view('create', compact('parents', 'keuzedelen'));
    })->name('create');

    Route::post('/keuzedeel/aanmaken', function(Request $request) {
        $user = auth()->user();
        if ($user->role !== 'admin') return redirect()->route('home');

        $request->validate([
            'id' => 'required|string|unique:keuzedelen,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'parent_id' => 'nullable|exists:keuzedelen,id',
            'parent_max' => 'nullable|integer|min:1',
            'deel_max' => 'required|integer|min:1',
            'start_inschrijving' => 'required|date',
            'eind_inschrijving' => 'required|date|after:start_inschrijving',
        ]);

        // Parent bepalen
        if ($request->parent_id) {
            $parentId = $request->parent_id;
            $parent = Keuzedeel::find($parentId);
            $title = $parent ? $parent->title : $request->title; // force subdeel title
        } else {
            $parent = Keuzedeel::create([
                'id' => (string) Str::uuid(),
                'title' => $request->title,
                'description' => '',
                'actief' => true,
                'is_open' => true,
                'minimum_studenten' => 15,
                'maximum_studenten' => $request->parent_max ?? 30,
                'start_inschrijving' => $request->start_inschrijving,
                'eind_inschrijving' => $request->eind_inschrijving,
            ]);
            $parentId = $parent->id;
            $title = $request->title;
        }

        // Subdeel aanmaken
        Keuzedeel::create([
            'id' => $request->id,
            'title' => $title,
            'description' => $request->description,
            'parent_id' => $parentId,
            'volgorde' => 1,
            'actief' => true,
            'is_open' => true,
            'minimum_studenten' => 15,
            'maximum_studenten' => $request->deel_max,
            'start_inschrijving' => $request->start_inschrijving,
            'eind_inschrijving' => $request->eind_inschrijving,
        ]);

        return redirect()->route('home')->with('success', 'Keuzedeel succesvol aangemaakt!');
    })->name('keuzedeel.store');

});
