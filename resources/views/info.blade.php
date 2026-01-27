@extends('layouts.app')

@section('title', 'Keuzedeel Info - ' . $keuzedeel->title)

@section('content')

@php
use Carbon\Carbon;

$now = Carbon::now();
$student = auth()->user()->student ?? null;

// Student active priorities and count
$activeInschrijvingen = $student
    ? $student->inschrijvingen()
        ->whereIn('status', ['goedgekeurd','ingediend', 'afgewezen'])
        ->pluck('priority')
        ->map(fn($p) => (int)$p)
        ->toArray()
    : [];
$activeCount = count($activeInschrijvingen);

// Determine for each deel which priorities are full
$maxReachedByPrio = [];
foreach ($delen as $deel) {
    $maxReachedByPrio[] = [
        1 => $deel->isPrioFull(1),
        2 => $deel->isPrioFull(2),
        3 => $deel->isPrioFull(3),
    ];
}
@endphp

<h1 id="hoofdtitel" class="text-3xl font-bold mb-4">
    {{ $keuzedeel->title }} - <span id="huidig-deel-titel">{{ $delen[0]->id ?? '' }}</span>
</h1>

<div class="flex items-start gap-6 mb-6">
    {{-- Deel buttons --}}
    <div class="flex gap-6 flex-wrap">
        @foreach($delen as $index => $deel)
            @php $status = $deel->status_helper; @endphp
            <button
                type="button"
                class="w-44 rounded shadow {{ $status->color() }} flex flex-col items-center p-2 deel-btn {{ $index !== 0 ? 'opacity-60' : 'opacity-100' }}"
                data-id="{{ $deel->id }}"
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

    {{-- Info boxes --}}
    <div class="flex flex-col gap-3 ml-auto items-end">
        <div id="aantal-ingeschreven"
             class="border px-4 py-2 rounded font-semibold text-center w-56 bg-white">
            Aantal ingeschreven:<br>
            <span>{{ $delen[0]->ingeschreven_count ?? 0 }}</span>
        </div>

        <div class="flex gap-3 items-stretch">
            @if(auth()->user()->role === 'admin')
                <button onclick="openActiefModal()"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded font-semibold">
                    Actief status aanpassen
                </button>
            @endif

            <div id="actief-status-box"
                 class="px-4 py-2 rounded text-white font-semibold flex items-center
                 {{ $delen[0]->actief ? 'bg-green-600' : 'bg-red-600' }}">
                {{ $delen[0]->actief ? 'Actief' : 'Inactief' }}
            </div>

            <div id="datum-box"
                 class="border px-4 py-2 rounded font-semibold text-center w-56 bg-white">
                <div><p>Inschrijvingsperiode:</p></div>
                <div>
                    {{ \Carbon\Carbon::parse($delen[0]->start_inschrijving)->format('d-m-Y') }}
                    / {{ \Carbon\Carbon::parse($delen[0]->eind_inschrijving)->format('d-m-Y') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Description --}}
<section class="mb-4 p-4 border rounded bg-gray-50 text-gray-800">
    <div id="deel-beschrijving">
        {{ $delen[0]->description ?? '' }}
    </div>
</section>

{{-- Forms --}}
<div class="flex flex-col mt-4 gap-4" id="form-container">
@foreach($delen as $index => $deel)
<div class="deel-form" data-id="{{ $deel->id }}" style="display: {{ $index === 0 ? 'block' : 'none' }}">

    @php
        $inPeriod = $now->between($deel->start_inschrijving, $deel->eind_inschrijving);
        $canEnroll = $student && $activeCount < 3 && !$deel->is_ingeschreven && $inPeriod && $deel->actief;
    @endphp

    {{-- STUDENT --}}
    @if(auth()->user()->role === 'student')
        @php
            $reason = '';
            if(!$deel->actief) $reason = 'Keuzedeel niet actief';
            elseif(!$inPeriod) $reason = 'Buiten inschrijvingsperiode';
            elseif($activeCount >= 3) $reason = 'Maximum inschrijvingen bereikt';
        @endphp

        @if($deel->is_ingeschreven)
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-2">
                Je bent al ingeschreven voor dit keuzedeel.
            </div>

            <form method="POST" action="{{ route('uitschrijven.destroy') }}">
                @csrf
                <input type="hidden" name="keuzedeel_id" value="{{ $deel->id }}">
                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded">
                    Schrijf uit
                </button>
            </form>

        @else
            <div class="flex items-center gap-2">
                <button
                    onclick="openInschrijvingModal('{{ $deel->id }}')"
                    class="px-6 py-2 rounded text-white {{ $canEnroll ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed' }}"
                    {{ $canEnroll ? '' : 'disabled' }}>
                    Schrijf in
                </button>
                @if(!$canEnroll)
                    <span class="text-gray-600 italic text-sm">{{ $reason }}</span>
                @endif
            </div>
        @endif
    @endif

    {{-- ADMIN / DOCENT --}}
    @if(in_array(auth()->user()->role, ['admin','docent']))
        <a href="{{ route('keuzedeel.edit', $deel->id) }}"
           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded inline-block mt-2">
            Pas info aan
        </a>
    @endif

</div>
@endforeach
</div>

{{-- Student priority modal --}}
@if(auth()->user()->role === 'student')
<div id="inschrijving-modal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded shadow-lg p-6 w-96">
        <h2 class="text-xl font-bold mb-4">Inschrijven</h2>

        <form method="POST" action="{{ route('inschrijven.store') }}">
            @csrf
            <input type="hidden" name="keuzedeel_id" id="inschrijving-keuzedeel-id">

            <label class="block font-semibold mb-1">Prioriteit</label>
            <select name="priority" id="priority-select" required class="border rounded w-full px-2 py-1 mb-3">
                <option value="">Kies prioriteit</option>
            </select>

            <label class="block font-semibold mb-1">Opmerkingen</label>
            <textarea name="opmerkingen"
                      class="border rounded w-full px-2 py-1 mb-4"
                      rows="2"></textarea>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeInschrijvingModal()"
                        class="border px-4 py-2 rounded">Annuleren</button>
                <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded">
                    Bevestigen
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Admin actief modal --}}
@if(auth()->user()->role === 'admin')
<div id="actief-modal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded shadow-lg p-6 w-96">
        <h2 class="text-xl font-bold mb-4">Actief status wijzigen</h2>

        <form method="POST" id="actief-form">
            @csrf
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeActiefModal()"
                        class="border px-4 py-2 rounded">Annuleren</button>
                <button type="submit"
                        class="bg-yellow-500 text-white px-4 py-2 rounded">
                    Bevestigen
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@php
$ids = $delen->pluck('id');
$beschrijvingen = $delen->pluck('description');
$actief = $delen->pluck('actief');
$aantallen = $delen->pluck('ingeschreven_count');
$startData = $delen->pluck('start_inschrijving');
$eindData = $delen->pluck('eind_inschrijving');
@endphp

<script>
const studentActivePriorities = @json($activeInschrijvingen);
const studentActiveCount = @json($activeCount);
const maxReachedByPrio = @json($maxReachedByPrio);

const ids = @json($ids);
const beschrijvingen = @json($beschrijvingen);
const actief = @json($actief);
const aantallen = @json($aantallen);
const startData = @json($startData);
const eindData = @json($eindData);

function formatDateDMY(dateStr){
    if(!dateStr) return '';
    let [datePart] = dateStr.split('T');
    let [year, month, day] = datePart.split('-');
    return `${day}-${month}-${year}`;
}

function selectDeelById(id){
    document.querySelectorAll('.deel-btn').forEach((btn,i)=>{
        const active = btn.dataset.id === id;
        btn.classList.toggle('opacity-100', active);
        btn.classList.toggle('opacity-60', !active);

        if(active){
            document.getElementById('huidig-deel-titel').textContent = ids[i];
            document.getElementById('deel-beschrijving').textContent = beschrijvingen[i];
            document.querySelector('#aantal-ingeschreven span').textContent = aantallen[i] ?? 0;

            const box = document.getElementById('actief-status-box');
            box.textContent = actief[i] ? 'Actief' : 'Inactief';
            box.className = actief[i]
                ? 'px-4 py-2 rounded text-white font-semibold flex items-center bg-green-600'
                : 'px-4 py-2 rounded text-white font-semibold flex items-center bg-red-600';

            const datumBox = document.getElementById('datum-box');
            datumBox.innerHTML = `
                <div><p>Inschrijvingsperiode:</p></div>
                <div>${formatDateDMY(startData[i])} / ${formatDateDMY(eindData[i])}</div>
            `;

            document.querySelectorAll('.deel-form').forEach(f=>{
                f.style.display = f.dataset.id===id ? 'block':'none';
            });

            @if(auth()->user()->role==='admin')
                document.getElementById('actief-form').action=`/keuzedeel/${id}/toggle-actief`;
            @endif
        }
    });

    const url = new URL(window.location);
    url.searchParams.set('id', id);
    window.history.replaceState({}, '', url);
}

document.querySelectorAll('.deel-btn').forEach(btn =>
    btn.addEventListener('click', () => selectDeelById(btn.dataset.id))
);

const initialId = new URLSearchParams(window.location.search).get('id') ?? ids[0];
if(initialId) selectDeelById(initialId);

function openInschrijvingModal(id){
    document.getElementById('inschrijving-keuzedeel-id').value = id;

    const select = document.getElementById('priority-select');
    select.innerHTML = '<option value="">Kies prioriteit</option>';

    const deelIndex = ids.indexOf(id);

    [1,2,3].forEach(p => {
        const studentHasPrio = studentActivePriorities.includes(p);
        const prioFull = maxReachedByPrio[deelIndex][p];

        if(!studentHasPrio && !prioFull){
            const opt = document.createElement('option');
            opt.value = p;
            opt.textContent = p;
            select.appendChild(opt);
        }
    });

    select.disabled = select.options.length <= 1;

    document.getElementById('inschrijving-modal').classList.remove('hidden');
    document.getElementById('inschrijving-modal').classList.add('flex');
}

function closeInschrijvingModal(){
    document.getElementById('inschrijving-modal').classList.add('hidden');
}

function openActiefModal(){
    document.getElementById('actief-modal').classList.remove('hidden');
    document.getElementById('actief-modal').classList.add('flex');
}

function closeActiefModal(){
    document.getElementById('actief-modal').classList.add('hidden');
}
</script>

@endsection
