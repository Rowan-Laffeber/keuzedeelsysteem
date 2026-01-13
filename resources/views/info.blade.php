@extends('layouts.app')

@section('title', 'Keuzedeel Info - ' . $keuzedeel->title)

@section('content')

<h1 id="hoofdtitel" class="text-3xl font-bold mb-4">
    {{ $keuzedeel->title }} -
    <span id="huidig-deel-titel">{{ $delen[0]->id ?? '' }}</span>
</h1>

@php
class StatusHelper {
    public string $status;
    public int $max;
    public int $ingeschreven;

    const MINIMUM_INSCHRIJVINGEN = 15;

    public function __construct(string $status, int $max = 0, int $ingeschreven = 0) {
        $this->max = $max;
        $this->ingeschreven = $ingeschreven;

        if ($this->ingeschreven >= $this->max) {
            $this->status = 'geen_plek';
        } elseif ($status === 'nog_plek' && $this->ingeschreven < self::MINIMUM_INSCHRIJVINGEN) {
            $this->status = 'niet_genoeg';
        } else {
            $this->status = $status;
        }
    }

    public function color(): string {
        return match($this->status) {
            'nog_plek' => 'bg-blue-300',
            'niet_genoeg' => 'bg-orange-300',
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
            'niet_genoeg' => 'text-orange-900',
            'afgerond' => 'text-green-900',
            'keuze1' => 'text-yellow-900',
            'keuze2' => 'text-yellow-800',
            'geen_plek' => 'text-red-900',
            default => 'text-gray-900',
        };
    }

    public function text(): string {
        return match($this->status) {
            'nog_plek' => 'Nog ' . max(0, $this->max - $this->ingeschreven) . ' plaatsen',
            'niet_genoeg' => 'Niet genoeg inschrijvingen!',
            'afgerond' => 'Afgerond',
            'keuze1' => '1e keus',
            'keuze2' => '2e keus',
            'geen_plek' => 'Geen plaats',
            default => 'Onbekend',
        };
    }
}
@endphp

<div class="flex items-start gap-6 mb-6">

    <div class="flex gap-6 flex-wrap">
        @foreach($delen as $index => $deel)
            @php
                $ingeschreven = $deel->ingeschreven ?? 0;
                $statusText = $deel->is_open ? 'nog_plek' : 'afgerond';
                $status = new StatusHelper($statusText, $deel->maximum_studenten, $ingeschreven);
            @endphp

            <button
                type="button"
                class="w-44 rounded shadow {{ $status->color() }} flex flex-col items-center p-2 deel-btn {{ $index !== 0 ? 'opacity-60' : 'opacity-100' }}"
                data-id="{{ $deel->id }}"
                data-index="{{ $index }}"
            >
                <div class="text-center font-semibold mb-2 {{ $status->textColor() }}">
                    {{ $status->text() }}
                </div>

                <div class="bg-white p-4 rounded w-full text-center font-bold text-lg">
                    {{ $deel->id }}
                </div>
            </button>
        @endforeach
    </div>

    <div class="flex flex-col justify-center space-y-4 ml-auto">
        <div id="aantal-ingeschreven" class="border px-4 py-2 rounded font-semibold text-center w-48">
            Aantal ingeschreven:<br>
            <span>{{ $delen[0]->ingeschreven ?? 0 }}</span>
        </div>
    </div>

</div>

<section class="mb-4 p-4 border rounded bg-gray-50 text-gray-800">
    <div class="mb-2">
        {{ $keuzedeel->description }}
    </div>
    <div id="deel-beschrijving">
        {{ $delen[0]->description ?? '' }}
    </div>
</section>

<div class="flex justify-between mt-4 space-x-4">
    @if(auth()->user()->role === 'student')
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            Schrijf in
        </button>
    @endif

    @if(in_array(auth()->user()->role, ['admin','docent']))
        <button class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
            Pas info aan
        </button>
    @endif

    @if(auth()->user()->role === 'admin')
        <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded">
            Actief status aanpassen
        </button>
    @endif
</div>

@php
$js_ids = [];
$js_aantalIngeschreven = [];
$js_beschrijvingen = [];

foreach ($delen as $deel) {
    $js_ids[] = $deel->id;
    $js_aantalIngeschreven[] = $deel->ingeschreven ?? 0;
    $js_beschrijvingen[] = $deel->description ?? '';
}
@endphp

<script>
const deelButtons = document.querySelectorAll('.deel-btn');
const aantalEl = document.querySelector('#aantal-ingeschreven span');
const titelSpan = document.getElementById('huidig-deel-titel');
const beschrijvingEl = document.getElementById('deel-beschrijving');

const ids = @json($js_ids);
const aantallen = @json($js_aantalIngeschreven);
const beschrijvingen = @json($js_beschrijvingen);

function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

function selectDeelById(id) {
    deelButtons.forEach((btn, i) => {
        const active = btn.dataset.id === id;
        btn.classList.toggle('opacity-100', active);
        btn.classList.toggle('opacity-60', !active);

        if (active) {
            aantalEl.textContent = aantallen[i] ?? 0;
            titelSpan.textContent = ids[i] ?? '';
            beschrijvingEl.textContent = beschrijvingen[i] ?? '';
        }
    });

    const url = new URL(window.location);
    url.searchParams.set('deel', id);
    window.history.replaceState({}, '', url);
}

const initialDeel =
    getQueryParam('deel') && ids.includes(getQueryParam('deel'))
        ? getQueryParam('deel')
        : ids[0];

selectDeelById(initialDeel);

deelButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        selectDeelById(btn.dataset.id);
    });
});
</script>

@endsection
