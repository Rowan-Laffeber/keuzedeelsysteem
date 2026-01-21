@extends('layouts.app')

@section('title', 'Keuzedeel Info - ' . $keuzedeel->title)

@section('content')

<h1 id="hoofdtitel" class="text-3xl font-bold mb-4">
    {{ $keuzedeel->title }} -
    <span id="huidig-deel-titel">{{ $delen[0]->id ?? '' }}</span>
</h1>

<div class="flex items-start gap-6 mb-6">

    <div class="flex gap-6 flex-wrap">
        @foreach($delen as $index => $deel)
            @php
                $status = $deel->status_helper;
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
    <div id="deel-beschrijving">
        {{ $delen[0]->description ?? '' }}
    </div>
</section>

{{-- Forms and buttons rendered in Blade --}}
<div class="flex flex-col mt-4 gap-4" id="form-container">
    @foreach($delen as $index => $deel)
        <div class="deel-form" data-id="{{ $deel->id }}" style="display: {{ $index === 0 ? 'block' : 'none' }}">
            
            {{-- Student buttons --}}
            @if(auth()->user()->role === 'student')
                @if($deel->is_ingeschreven)
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-3">
                        <strong>Al ingeschreven!</strong> Je bent al ingeschreven voor dit keuzedeel.
                    </div>
                    <form method="POST" action="{{ route('uitschrijven.destroy') }}">
                        @csrf
                        <input type="hidden" name="keuzedeel_id" value="{{ $deel->id }}">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded">
                            Schrijf uit
                        </button>
                    </form>
                @elseif(($deel->ingeschreven_count ?? 0) >= ($deel->maximum_studenten ?? 30))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <strong>Vol!</strong> Dit keuzedeel zit helaas vol.
                    </div>
                @else
                    <form method="POST" action="{{ route('inschrijven.store') }}">
                        @csrf
                        <input type="hidden" name="keuzedeel_id" value="{{ $deel->id }}">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                            Schrijf in
                        </button>
                    </form>
                @endif
            @endif

            {{-- Admin / Docent buttons --}}
            @if(in_array(auth()->user()->role, ['admin','docent']))
                <a href="{{ route('keuzedeel.edit', $deel->id) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded inline-block mt-2">
                    Pas info aan
                </a>
            @endif

            {{-- Admin only --}}
            @if(auth()->user()->role === 'admin')
                <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded mt-2">
                    Actief status aanpassen
                </button>
            @endif

        </div>
    @endforeach
</div>

@php
$js_ids = [];
$js_aantalIngeschreven = [];
$js_beschrijvingen = [];

foreach ($delen as $deel) {
    $js_ids[] = $deel->id;
    $js_aantalIngeschreven[] = $deel->ingeschreven_count ?? 0;
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

            // Show only the selected deel form
            document.querySelectorAll('.deel-form').forEach(form => {
                form.style.display = form.dataset.id === id ? 'block' : 'none';
            });
        }
    });

    const url = new URL(window.location);
    url.searchParams.set('id', id);
    window.history.replaceState({}, '', url);
}

function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

// --- NEW: pick initial deel from session first ---
const sessionSubdeel = @json(session('subdeel_id')); // flash from controller

const initialDeel =
    (sessionSubdeel && ids.includes(sessionSubdeel)) ? sessionSubdeel :
    (getQueryParam('id') && ids.includes(getQueryParam('id'))) ? getQueryParam('id') :
    (ids.length > 0 ? ids[0] : null);

if (initialDeel) {
    selectDeelById(initialDeel);
}

// Click listeners for subdeel buttons
deelButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        selectDeelById(btn.dataset.id);
    });
});
</script>

@endsection
