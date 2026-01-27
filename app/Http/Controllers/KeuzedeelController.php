<?php

namespace App\Http\Controllers;

use App\Models\Keuzedeel;
use App\Models\Inschrijving;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\PriorityStatusService;
use Illuminate\Support\Facades\DB;

class KeuzedeelController extends Controller
{
    public function create()
    {
        $this->authorizeAdmin();

        $parents = Keuzedeel::whereNull('parent_id')->get();
        $keuzedelen = [];
        $seenIds = []; // track IDs across all files

        $uploadFolder = storage_path('app/csv_uploads');

        if (is_dir($uploadFolder)) {
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
                            if (strpos($cell, 'K') !== false
                                && !Keuzedeel::where('id', $cell)->exists()
                                && !in_array($cell, $seenIds)
                            ) {
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

    public function store(Request $request)
    {
        $this->authorizeAdmin();

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

        if ($request->parent_id) {
            // Use old creation logic for subdeel creation under existing parent
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
            } else {
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
                'maximum_studenten' => 30,
                'parent_max_type' => $parentMaxType,
                'start_inschrijving' => $request->start_inschrijving,
                'eind_inschrijving' => $request->eind_inschrijving,
            ]);
            $title = $request->title;
        }

        // Create the actual subdeel
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
        $this->authorizeAdmin();
        return view('update', compact('keuzedeel'));
    }

    public function update(Request $request, Keuzedeel $keuzedeel)
    {
        $this->authorizeAdmin();

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'start_inschrijving' => 'required|date',
            'eind_inschrijving' => 'required|date|after:start_inschrijving',
        ]);

        $keuzedeel->update([
            'description' => $request->description,
            'start_inschrijving' => $request->start_inschrijving,
            'eind_inschrijving' => $request->eind_inschrijving,
        ]);

        $parent = Keuzedeel::find($keuzedeel->parent_id ?? $keuzedeel->id);
        if ($parent) {
            $parent->title = $request->title;
            $parent->save();

            Keuzedeel::where('parent_id', $parent->id)
                ->update(['title' => $request->title]);
        }

        return redirect()->route('keuzedeel.info', $parent->id)
            ->with('success', 'Keuzedeel en subdelen aangepast!')
            ->with('subdeel_id', $keuzedeel->id);
    }

    public function toggleActief(Keuzedeel $keuzedeel)
    {
        $this->authorizeAdmin();

        $keuzedeel->actief = !$keuzedeel->actief;
        $keuzedeel->save();

        $studentIds = Inschrijving::where('keuzedeel_id', $keuzedeel->id)
            ->pluck('student_id')
            ->unique()
            ->toArray();

        foreach ($studentIds as $studentId) {
            PriorityStatusService::recalc(studentId: $studentId);
        }

        return back()->with('success', $keuzedeel->actief
            ? 'Keuzedeel geactiveerd en inschrijvingen herberekend.'
            : 'Keuzedeel gedeactiveerd en inschrijvingen herberekend.');
    }

    public function destroy(Keuzedeel $keuzedeel)
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($keuzedeel) {
            $studentIds = Inschrijving::where('keuzedeel_id', $keuzedeel->id)
                ->pluck('student_id')
                ->unique()
                ->toArray();

            Inschrijving::where('keuzedeel_id', $keuzedeel->id)->delete();

            $parentId = $keuzedeel->parent_id;
            $keuzedeel->delete();

            if ($parentId) {
                $parent = Keuzedeel::find($parentId);
                if ($parent && $parent->delen()->count() === 0) {
                    $parent->delete();
                }
            }

            foreach ($studentIds as $studentId) {
                PriorityStatusService::recalc(studentId: $studentId);
            }
        });

        return redirect()->route('home')->with('success', 'Keuzedeel en inschrijvingen verwijderd.');
    }

    private function authorizeAdmin()
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            abort(403, 'Alleen admins mogen dit doen.');
        }
    }
}
