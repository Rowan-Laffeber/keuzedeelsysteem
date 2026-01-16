<?php
/*                  
///////////////////////////////////
            OUTDATED   
/////////////////////////////////
*/


namespace Database\Seeders;

use App\Models\Keuzedeel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KeuzedeelSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $future = now()->addYears(50);

        $titels = [
            'Webdevelopment Basics',
            'OOP Basics',
            'Design Thinking',
            'Data-analyse',
            'Game Design',
            'Cybersecurity Basics',
        ];

        foreach ($titels as $titel) {

            // Parent (zichtbaar op home)
            $parent = Keuzedeel::create([
                'id' => (string) Str::uuid(),
                'title' => $titel,
                'description' => "Overzicht van $titel",
                'actief' => true,
                'is_open' => true,
                'minimum_studenten' => 15,
                'maximum_studenten' => 0,
                'start_inschrijving' => $now,
                'eind_inschrijving' => $future,
            ]);

            // Deel 1
            Keuzedeel::create([
                'id' => (string) Str::uuid(),
                'title' => "$titel - Deel 1",
                'description' => "Inhoud van $titel Deel 1",
                'parent_id' => $parent->id,
                'volgorde' => 1,
                'actief' => true,
                'is_open' => true,
                'minimum_studenten' => 15,
                'maximum_studenten' => 30,
                'start_inschrijving' => $now,
                'eind_inschrijving' => $future,
            ]);

            // Deel 2
            Keuzedeel::create([
                'id' => (string) Str::uuid(),
                'title' => "$titel - Deel 2",
                'description' => "Inhoud van $titel Deel 2",
                'parent_id' => $parent->id,
                'volgorde' => 2,
                'actief' => true,
                'is_open' => true,
                'minimum_studenten' => 15,
                'maximum_studenten' => 30,
                'start_inschrijving' => $now,
                'eind_inschrijving' => $future,
            ]);
        }
    }
}
