<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Keuzedeel Info</title>
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

@php
class StatusHelper {
    public string $status;

    public function __construct(string $status) {
        $this->status = $status;
    }

    public function color(): string {
        return match($this->status) {
            'nog_plek' => 'bg-blue-300',
            'afgerond' => 'bg-green-300',
            'keuze1' => 'bg-yellow-300',
            'keuze2' => 'bg-yellow-200',
            'geen_plek' => 'bg-red-300',
            default => 'bg-gray-300',
        };
    }

    public function textColor(): string {
        return match($this->status) {
            'nog_plek' => 'text-blue-900',
            'afgerond' => 'text-green-900',
            'keuze1' => 'text-yellow-900',
            'keuze2' => 'text-yellow-800',
            'geen_plek' => 'text-red-900',
            default => 'text-gray-900',
        };
    }

    public function text(): string {
        return match($this->status) {
            'nog_plek' => 'Nog X plaatsen',
            'afgerond' => 'Afgerond',
            'keuze1' => '1e keus',
            'keuze2' => '2e keus',
            'geen_plek' => 'Geen plaats',
            default => 'Onbekend',
        };
    }
}

$keuzedeel = (object)[
    'title' => 'Keuzedeel Webdevelopment Basics',
    'deel1_status' => 'afgerond',
    'deel2_status' => 'nog_plek',
    'description' => 'Dit keuzedeel leert je de basis van webontwikkeling, inclusief HTML, CSS en JavaScript. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    'aantal_ingeschreven' => 15,
    'is_admin' => true,
];

$deel1 = new StatusHelper($keuzedeel->deel1_status);
$deel2 = new StatusHelper($keuzedeel->deel2_status);
@endphp

<main class="max-w-7xl mx-auto p-6">

    {{-- Title --}}
    <h1 class="text-3xl font-bold mb-6">{{ $keuzedeel->title }}</h1>

    {{-- Deel1 & Deel2 + Admin panel --}}
    <div class="flex items-start gap-6 mb-6">

        <div class="flex gap-6">

            {{-- Deel 1 wrapper --}}
            <div class="w-40 rounded shadow {{ $deel1->color() }} flex flex-col items-center p-2">
                {{-- Status text --}}
                <div class="text-center font-semibold mb-2 {{ $deel1->textColor() }}">
                    {{ $deel1->text() }}
                </div>
                {{-- White tile --}}
                <div class="bg-white p-4 rounded w-full text-center font-bold text-lg">
                    Deel 1
                </div>
            </div>

            {{-- Deel 2 wrapper --}}
            <div class="w-40 rounded shadow {{ $deel2->color() }} flex flex-col items-center p-2">
                {{-- Status text --}}
                <div class="text-center font-semibold mb-2 {{ $deel2->textColor() }}">
                    {{ $deel2->text() }}
                </div>
                {{-- White tile --}}
                <div class="bg-white p-4 rounded w-full text-center font-bold text-lg">
                    Deel 2
                </div>
            </div>

        </div>

        {{-- Admin vertical panel --}}
        @if($keuzedeel->is_admin)
            <div class="flex flex-col justify-center space-y-4 ml-auto">
                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded w-48">
                    Actief status aanpassen
                </button>
                <div class="border px-4 py-2 rounded font-semibold text-center w-48">
                    Aantal ingeschreven:<br>{{ $keuzedeel->aantal_ingeschreven }}
                </div>
            </div>
        @endif

    </div>

    {{-- Description --}}
    <section class="mb-6 p-4 border rounded bg-gray-50 text-gray-800">
        {{ $keuzedeel->description }}
    </section>

    {{-- User action buttons --}}
    <div class="flex justify-between">
        <button class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
            Pas info aan
        </button>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            Schrijf in
        </button>
    </div>

</main>

</body>
</html>
