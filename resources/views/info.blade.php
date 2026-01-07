<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Keuzedeel Info - {{ $keuzedeel->title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex-shrink-0">
                <img class="h-10 w-10" src="{{ asset('images/placeholder.png') }}" alt="Logo">
            </div>
            <div class="hidden md:flex space-x-6">
                <a href="{{ route('home') }}" class="text-gray-700 hover:text-blue-600 font-medium">Home</a>
                <a href="#" class="text-gray-700 hover:text-blue-600 font-medium">Overzicht</a>
                <a href="#" class="text-gray-700 hover:text-blue-600 font-medium">Contact</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="#" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Login</a>
            </div>
        </div>
    </div>
</header>

@php
class StatusHelper {
    public string $status;
    public int $max;
    public int $ingeschreven;
    public function __construct(string $status, int $max = 0, int $ingeschreven = 0) {
        $this->status = $status;
        $this->max = $max;
        $this->ingeschreven = $ingeschreven;
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
        if ($this->status === 'nog_plek') {
            $beschikbaar = $this->max - $this->ingeschreven;
            return 'Nog ' . max(0, $beschikbaar) . ' plaatsen';
        }
        return match($this->status) {
            'afgerond' => 'Afgerond',
            'keuze1' => '1e keus',
            'keuze2' => '2e keus',
            'geen_plek' => 'Geen plaats',
            default => 'Onbekend',
        };
    }
}
@endphp

<main class="max-w-7xl mx-auto p-6">

    <h1 id="hoofdtitel" class="text-3xl font-bold mb-4">
        {{ $keuzedeel->title }} - <span id="huidig-deel-titel">Deel 1</span>
    </h1>

    <div class="flex items-start gap-6 mb-6">

        <div class="flex gap-6">
            @foreach($delen as $index => $deel)
                @php
                    $statusText = $deel->is_open ? 'nog_plek' : 'afgerond';
                    $status = new StatusHelper($statusText, $deel->maximum_studenten, $deel->minimum_studenten);
                @endphp
                <button
                    type="button"
                    class="w-40 rounded shadow {{ $status->color() }} flex flex-col items-center p-2 deel-btn {{ $index !== 0 ? 'opacity-60' : 'opacity-100' }}"
                    data-index="{{ $index }}"
                    data-max="{{ $deel->maximum_studenten }}"
                    data-ingeschreven="{{ $deel->minimum_studenten }}"
                    data-description="{{ htmlspecialchars($deel->description) }}"
                >
                    <div class="text-center font-semibold mb-2 {{ $status->textColor() }}">
                        {{ $status->text() }}
                    </div>
                    <div class="bg-white p-4 rounded w-full text-center font-bold text-lg">
                        Deel {{ $index + 1 }}
                    </div>
                </button>
            @endforeach
        </div>

        <div class="flex flex-col justify-center space-y-4 ml-auto">
            <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded w-48">
                Actief status aanpassen
            </button>
            <div id="aantal-ingeschreven" class="border px-4 py-2 rounded font-semibold text-center w-48">
                Aantal ingeschreven:<br>
                <span>{{ $delen[0]->minimum_studenten ?? 0 }}</span>
            </div>
        </div>
    </div>

    <section class="mb-4 p-4 border rounded bg-gray-50 text-gray-800">
        <div>
            {{ $keuzedeel->description }}
        </div>
        <div id="deel-beschrijving">
            {{ $delen[0]->description }}
        </div>
    </section>

    <div class="flex justify-between">
        <button class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
            Pas info aan
        </button>
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            Schrijf in
        </button>
    </div>

</main>

<script>
    const deelButtons = document.querySelectorAll('.deel-btn');
    const aantalEl = document.getElementById('aantal-ingeschreven').querySelector('span');
    const titelSpan = document.getElementById('huidig-deel-titel');
    const beschrijvingEl = document.getElementById('deel-beschrijving');

    const deelSuffixes = @json($delen->map(fn($d, $i) => 'Deel ' . ($i + 1)));
    const aantalIngeschreven = @json($delen->pluck('minimum_studenten'));
    const beschrijvingen = @json($delen->pluck('description'));

    // helper om query param te lezen
    function getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    // Bepaal geselecteerd deel
    let selectedIndex = parseInt(getQueryParam('deel') ?? 1, 10) - 1;
    if (selectedIndex < 0 || selectedIndex >= deelButtons.length) {
        selectedIndex = 0;
    }

    let currentIndex = selectedIndex;

    function selectDeel(idx) {
        deelButtons.forEach((b, i) => {
            b.classList.toggle('opacity-100', i === idx);
            b.classList.toggle('opacity-60', i !== idx);
        });

        aantalEl.textContent = aantalIngeschreven[idx] ?? 0;
        titelSpan.textContent = deelSuffixes[idx] ?? '';
        beschrijvingEl.textContent = beschrijvingen[idx] ?? '';

        currentIndex = idx;

        // update URL zonder reload
        const url = new URL(window.location);
        url.searchParams.set('deel', idx + 1);
        window.history.replaceState({}, '', url);
    }

    // initialiseer geselecteerd deel
    selectDeel(selectedIndex);

    // klik events
    deelButtons.forEach((btn, idx) => {
        btn.addEventListener('click', () => {
            if (currentIndex === idx) return;
            selectDeel(idx);
        });
    });
</script>

</body>
</html>
