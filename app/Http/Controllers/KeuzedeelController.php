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
                            // Only add new keuzedeel IDs starting with K
                            if (strpos($cell, 'K') !== false && !Keuzedeel::where('id', $cell)->exists()) {
                                $keuzedelen[] = $cell;
                            }
                        }
                        break; // stop reading after first data row following 'Examinerend'
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

            // Force backend consistency
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
                'is_open' => true,
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
            'is_open' => true,
            'minimum_studenten' => 15,
            'maximum_studenten' => $subdeelMax,
            'parent_max_type' => $parentMaxType,
            'start_inschrijving' => $request->start_inschrijving,
            'eind_inschrijving' => $request->eind_inschrijving,
        ]);

        return redirect()->route('home')->with('success', 'Keuzedeel succesvol aangemaakt!');
    }
}
