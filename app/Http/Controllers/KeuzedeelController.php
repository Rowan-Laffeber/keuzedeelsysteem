<?php

namespace App\Http\Controllers;

use App\Models\Keuzedeel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KeuzedeelController extends Controller
{
    /**
     * Show the form to create a new keuzedeel.
     */
    public function create()
    {   
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Alleen admins mogen deze pagina bekijken.');
        }
        
        $parents = Keuzedeel::whereNull('parent_id')->get();
        $keuzedelen = [];
        $seenIds = []; // track IDs across all files

        $uploadFolder = storage_path('app/csv_uploads');

        if (is_dir($uploadFolder)) {

            // Scan all CSV files in the folder
            $files = glob($uploadFolder . '/*.csv');

            foreach ($files as $csvFile) {

                $firstLine = file($csvFile)[0] ?? '';
                $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
                $handle = fopen($csvFile, 'r');

                if (!$handle) continue;

                $foundExaminerend = false;

                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    if (in_array('Examinerend', $row)) {
                        $foundExaminerend = true;
                        continue;
                    }

                    if ($foundExaminerend) {
                        foreach ($row as $cell) {
                            $cell = trim($cell);
                            if (strpos($cell, 'K') !== false && !Keuzedeel::where('id', $cell)->exists() && !in_array($cell, $seenIds)) {
                                $keuzedelen[] = $cell;
                                $seenIds[] = $cell;
                            }
                        }
                        break;
                    }
                }

                fclose($handle);
            }
        }

        return view('create', compact('parents', 'keuzedelen'));
    }

    /**
     * Store a newly created keuzedeel in the database.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home');
        }

        $request->validate([
            'id' => 'required|string|unique:keuzedelen,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'parent_id' => 'nullable|exists:keuzedelen,id',
            'parent_max_type' => 'nullable|string|in:subdeel,parent',
            'start_inschrijving' => 'required|date',
            'eind_inschrijving' => 'required|date|after:start_inschrijving',
        ]);

        $parentMaxType = $request->parent_max_type ?? 'subdeel';

        // Determine parent
        if ($request->parent_id) {
            $parent = Keuzedeel::find($request->parent_id);
            $title = $parent->title;

            $parentMaxType = $parent->parent_max_type ?? 'subdeel';
            if (!in_array($parentMaxType, ['subdeel', 'parent'])) {
                $parentMaxType = 'subdeel';
            }

            if ($parentMaxType === 'subdeel') {
                $subdeelMax = 30;
                $parent->maximum_studenten = ($parent->maximum_studenten ?? 0) + 30;
                $parent->save();
            } else { // parent max type
                $subdeelMax = null;
                if ($parent->maximum_studenten === null) {
                    $parent->maximum_studenten = 30;
                    $parent->save();
                }
            }

        } else {
            // Create new parent
            $subdeelMax = $parentMaxType === 'subdeel' ? 30 : null;

            $parent = Keuzedeel::create([
                'id' => (string) Str::uuid(),
                'title' => $request->title,
                'description' => '',
                'actief' => true,
                'minimum_studenten' => 15,
                'maximum_studenten' => 30, // always 30 for new parent
                'parent_max_type' => $parentMaxType,
                'start_inschrijving' => $request->start_inschrijving,
                'eind_inschrijving' => $request->eind_inschrijving,
            ]);
            $title = $request->title;
        }

        // Create subdeel
        Keuzedeel::create([
            'id' => $request->id,
            'title' => $title,
            'description' => $request->description,
            'parent_id' => $request->parent_id ?? $parent->id,
            'volgorde' => 1,
            'actief' => true,
            'minimum_studenten' => 15,
            'maximum_studenten' => $subdeelMax,
            'parent_max_type' => $parentMaxType,
            'start_inschrijving' => $request->start_inschrijving,
            'eind_inschrijving' => $request->eind_inschrijving,
        ]);

        return redirect()->route('home')->with('success', 'Keuzedeel succesvol aangemaakt!');
    }
    public function edit(Keuzedeel $keuzedeel)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home');
        }

        return view('update', compact('keuzedeel'));
    }

    public function update(Request $request, Keuzedeel $keuzedeel)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_inschrijving' => 'required|date',
            'eind_inschrijving' => 'required|date|after:start_inschrijving',
        ]);

        // Update the edited keuzedeel's description and dates
        $keuzedeel->update([
            'description' => $request->description,
            'start_inschrijving' => $request->start_inschrijving,
            'eind_inschrijving' => $request->eind_inschrijving,
        ]);

        // Determine the parent
        $parentId = $keuzedeel->parent_id ?? $keuzedeel->id;
        $parent = Keuzedeel::find($parentId);

        if ($parent) {
            // Update the parent title
            $parent->title = $request->title;
            $parent->save();

            // Update all subdelen of this parent to have the same title
            Keuzedeel::where('parent_id', $parent->id)
                ->update(['title' => $request->title]);
        }

        // Redirect to keuzedeel.info using parent_id as main route,
        // and pass current subdeel id as query param "id"
        return redirect()->route('keuzedeel.info', $parent->id) // parent route
            ->with('success', 'Keuzedeel en bijbehorende subdelen succesvol aangepast!')
            ->with('subdeel_id', $keuzedeel->id); // flash subdeel ID

    }
    public function toggleActief(Request $request, Keuzedeel $keuzedeel)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            return redirect()->route('home');
        }

        // Toggle the subdeel status
        $keuzedeel->actief = ! $keuzedeel->actief;
        $keuzedeel->save();

        return back();
    }



}

