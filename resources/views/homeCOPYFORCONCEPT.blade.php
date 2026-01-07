<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Keuzedeel Systeem - Home</title>
    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

{{-- Top Bar --}}
<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">

            {{-- Logo --}}
            <div class="flex-shrink-0">
                <img class="h-10 w-10" src="{{ asset('images/placeholder.png') }}" alt="Logo">
            </div>

            {{-- Nav Links --}}
            <div class="hidden md:flex space-x-6">
                <a href="#" class="text-gray-700 hover:text-blue-600 font-medium">Home</a>
                <a href="#" class="text-gray-700 hover:text-blue-600 font-medium">Overzicht</a>
                <a href="#" class="text-gray-700 hover:text-blue-600 font-medium">Contact</a>
            </div>

            {{-- Login --}}
            <div class="flex items-center space-x-4">
                <a href="#" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Login</a>
            </div>

        </div>
    </div>
</header>

{{-- Grid Section --}}
<main class="max-w-7xl mx-auto p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">

        @php
        // OOP Tile class
        class Tile {
            public string $title;
            public string $description;
            public string $status; // 'nog_plek', 'deel_afgerond', 'keuze1', 'keuze2', 'geen_plek', 'afgerond'
            public string $image;

            public function __construct(string $title, string $description, string $status, string $image = 'placeholder.png') {
                $this->title = $title;
                $this->description = $description;
                $this->status = $status;
                $this->image = $image;
            }

            public function statusText(): string {
                return match($this->status) {
                    'nog_plek' => 'Nog X plaatsen',
                    'deel_afgerond' => 'Deel 1 afgerond',
                    'keuze1' => '1e keus',
                    'keuze2' => '2de keus',
                    'geen_plek' => 'Geen plaats',
                    'afgerond' => 'Afgerond',
                    default => 'Onbekend',
                };
            }

            public function wrapperColor(): string {
                return match($this->status) {
                    'nog_plek' => 'bg-blue-300',
                    'deel_afgerond' => 'bg-purple-300',
                    'keuze1' => 'bg-yellow-300',
                    'keuze2' => 'bg-yellow-200',
                    'geen_plek' => 'bg-red-300',
                    'afgerond' => 'bg-green-300',
                    default => 'bg-gray-300',
                };
            }

            public function statusTextColor(): string {
                return match($this->status) {
                    'nog_plek' => 'text-blue-900',
                    'deel_afgerond' => 'text-purple-900',
                    'keuze1' => 'text-yellow-900',
                    'keuze2' => 'text-yellow-800',
                    'geen_plek' => 'text-red-900',
                    'afgerond' => 'text-green-900',
                    default => 'text-gray-900',
                };
            }
        }

        // Voorbeeld tiles
        $tiles = [
            new Tile('Webdevelopment Basics', 'Beschrijving Webdevelopment Basics', 'nog_plek'),
            new Tile('OOP Basics', 'Beschrijving OOP Basics', 'deel_afgerond'),
            new Tile('Design Thinking', 'Beschrijving Design Thinking', 'keuze1'),   // 1e keus
            new Tile('Data-analyse', 'Beschrijving Data-analyse', 'keuze2'),      // 2e keus
            new Tile('Game Design', ' Beschrijving Game Design', 'geen_plek'),
            new Tile('Cybersecurity Basics', 'Beschrijving Cybersecurity Basics', 'afgerond'),
        ];
        @endphp

        @foreach($tiles as $tile)
            {{-- Tile wrapper --}}
            <div class="rounded shadow w-full {{ $tile->wrapperColor() }} p-2 flex flex-col items-center">
                
                {{-- Status badge --}}
                <div class="px-3 py-1 rounded-full mb-2 text-sm font-medium {{ $tile->statusTextColor() }}">
                    {{ $tile->statusText() }}
                </div>

                {{-- Tile content --}}
                <div class="bg-white p-4 rounded w-full flex flex-col items-center">
                    <h2 class="text-lg font-bold mb-2">{{ $tile->title }}</h2>
                    <p class="text-gray-600 mb-2 text-center">{{ $tile->description }}</p>
                    <img src="{{ asset('images/' . $tile->image) }}" alt="{{ $tile->title }}" class="w-full h-32 object-cover rounded">
                </div>

            </div>
        @endforeach

    </div>
</main>

</body>
</html>
