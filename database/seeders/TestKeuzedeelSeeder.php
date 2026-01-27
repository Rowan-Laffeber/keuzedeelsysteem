<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Keuzedeel;
use Illuminate\Support\Str;

class TestKeuzedeelSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $future = now()->addYears(50);

        // Realistic keuzedelen for testing
        $keuzedelenData = [
            [
                'title' => 'Webdevelopment Advanced',
                'description' => 'Gevorderde webontwikkeling met moderne frameworks zoals React, Vue.js en Angular.',
                'parent_description' => 'Overzicht van Webdevelopment Advanced - leer moderne frontend en backend technieken.'
            ],
            [
                'title' => 'Mobile App Development',
                'description' => 'Ontwikkel native en cross-platform mobiele applicaties voor iOS en Android.',
                'parent_description' => 'Overzicht van Mobile App Development - leer apps bouwen voor smartphones en tablets.'
            ],
            [
                'title' => 'Data Science & AI',
                'description' => 'Werken met big data, machine learning en kunstmatige intelligentie.',
                'parent_description' => 'Overzicht van Data Science & AI - duik in de wereld van data-analyse en AI.'
            ],
            [
                'title' => 'Cybersecurity Essentials',
                'description' => 'Basisprincipes van netwerkbeveiliging, ethisch hacken en digitale forensics.',
                'parent_description' => 'Overzicht van Cybersecurity Essentials - leer systemen beveiligen tegen digitale bedreigingen.'
            ],
            [
                'title' => 'Cloud Computing',
                'description' => 'Werken met cloudplatformen zoals AWS, Azure en Google Cloud.',
                'parent_description' => 'Overzicht van Cloud Computing - leer schaalbare applicaties bouwen in de cloud.'
            ],
            [
                'title' => 'UI/UX Design',
                'description' => 'Gebruikersinterface en gebruikerservaring design principes en tools.',
                'parent_description' => 'Overzicht van UI/UX Design - leer intuïtieve en mooie interfaces ontwerpen.'
            ],
            [
                'title' => 'DevOps Engineering',
                'description' => 'CI/CD pipelines, containerisatie en infrastructure as code.',
                'parent_description' => 'Overzicht van DevOps Engineering - leer development en operations combineren.'
            ],
            [
                'title' => 'Game Development',
                'description' => 'Game design, 2D/3D graphics en game engines zoals Unity en Unreal.',
                'parent_description' => 'Overzicht van Game Development - leer spellen maken van concept tot productie.'
            ],
            [
                'title' => 'Blockchain Technology',
                'description' => 'Cryptocurrency, smart contracts en gedecentraliseerde applicaties.',
                'parent_description' => 'Overzicht van Blockchain Technology - leer werken met distributed ledger technology.'
            ],
            [
                'title' => 'Internet of Things (IoT)',
                'description' => 'Connected devices, sensors en smart home technologie.',
                'parent_description' => 'Overzicht van Internet of Things - leer verbonden apparaten bouwen en programmeren.'
            ]
        ];

        foreach ($keuzedelenData as $data) {
            // Create parent keuzedeel (zichtbaar op home)
            $parent = Keuzedeel::firstOrCreate(
                ['title' => $data['title']],
                [
                    'id' => (string) Str::uuid(),
                    'description' => $data['parent_description'],
                    'actief' => true,
                    'minimum_studenten' => rand(10, 20),
                    'maximum_studenten' => 0, // Unlimited for parent
                    'start_inschrijving' => $now,
                    'eind_inschrijving' => $future,
                ]
            );

            // Create Deel 1
            Keuzedeel::firstOrCreate(
                ['title' => $data['title'] . ' - Deel 1'],
                [
                    'id' => (string) Str::uuid(),
                    'description' => $data['description'] . ' - Deel 1: Basisconcepten en fundamenten.',
                    'parent_id' => $parent->id,
                    'volgorde' => 1,
                    'actief' => true,
                    'minimum_studenten' => rand(8, 15),
                    'maximum_studenten' => rand(25, 35),
                    'start_inschrijving' => $now,
                    'eind_inschrijving' => $future,
                ]
            );

            // Create Deel 2
            Keuzedeel::firstOrCreate(
                ['title' => $data['title'] . ' - Deel 2'],
                [
                    'id' => (string) Str::uuid(),
                    'description' => $data['description'] . ' - Deel 2: Geavanceerde technieken en praktische toepassingen.',
                    'parent_id' => $parent->id,
                    'volgorde' => 2,
                    'actief' => true,
                    'minimum_studenten' => rand(8, 15),
                    'maximum_studenten' => rand(25, 35),
                    'start_inschrijving' => $now,
                    'eind_inschrijving' => $future,
                ]
            );
        }

        $this->command->info('✅ Test keuzedelen created successfully!');
        $this->command->info('📊 Total parent keuzedelen: ' . count($keuzedelenData));
        $this->command->info('📚 Total child keuzedelen: ' . (count($keuzedelenData) * 2));
    }
}
