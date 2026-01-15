@extends('layouts.app')

@section('title', 'Keuzedeel Info - ' . $keuzedeel->title)

@section('content')

@php
    // Redirect student if no eligible subdelen
    if(auth()->user()->role === 'student' && $delen->isEmpty()) {
        header("Location: " . route('home'));
        exit;
    }
@endphp

<h1 id="hoofdtitel" class="text-3xl font-bold mb-4">
    {{ $keuzedeel->title }} -
    <span id="huidig-deel-titel">{{ $delen[0]->id ?? '' }}</span>
</h1>

<div class="flex items-start gap-6 mb-6">

    <div class="flex gap-6 flex-wrap">
        @foreach($delen as $index => $deel)
            @php $status = $deel->status_helper; @endphp

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
    <div class="mb-2">{{ $keuzedeel->description }}</div>
    <div id="deel-beschrijving">{{ $delen[0]->description ?? '' }}</div>
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
const signedUpDisplay = document.querySelector('#aantal-ingeschreven span');
const titelSpan = document.getElementById('huidig-deel-titel');
const descriptionSection = document.getElementById('deel-beschrijving');

const ids = @json($js_ids);
const aantalIngeschreven = @json($js_aantalIngeschreven);
const beschrijvingen = @json($js_beschrijvingen);

function selectDeelById(id) {
    deelButtons.forEach((btn, i) => {
        const active = btn.dataset.id === id;
        btn.classList.toggle('opacity-100', active);
        btn.classList.toggle('opacity-60', !active);

        if (active) {
            signedUpDisplay.textContent = aantalIngeschreven[i] ?? 0;
            titelSpan.textContent = ids[i] ?? '';
            descriptionSection.textContent = beschrijvingen[i] ?? '';
        }
    });

    const url = new URL(window.location);
    url.searchParams.set('deel', id);
    window.history.replaceState({}, '', url);
}

// Pick initial deel from query param or default to first
const urlParams = new URLSearchParams(window.location.search);
const initialDeel = ids.includes(urlParams.get('deel')) ? urlParams.get('deel') : ids[0];
selectDeelById(initialDeel);

// Add click listeners to switch subdelen
deelButtons.forEach(btn => btn.addEventListener('click', () => selectDeelById(btn.dataset.id)));
</script>

@endsection
