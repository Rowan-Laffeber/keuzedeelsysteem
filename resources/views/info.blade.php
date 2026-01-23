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

    <div class="flex flex-col gap-3 ml-auto items-end">

        {{-- Aantal ingeschreven --}}
        <div id="aantal-ingeschreven"
             class="border px-4 py-2 rounded font-semibold text-center w-56 bg-white">
            Aantal ingeschreven:<br>
            <span>{{ $delen[0]->ingeschreven_count ?? 0 }}</span>
        </div>

        <div class="flex gap-3 items-stretch">
            {{-- Toggle button --}}
            @if(auth()->user()->role === 'admin')
                <button onclick="openActiefModal()"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded font-semibold">
                    Actief status aanpassen
                </button>
            @endif

            {{-- Actief status --}}
            <div id="actief-status-box"
                 class="px-4 py-2 rounded text-white font-semibold flex items-center
                 {{ $delen[0]->actief ? 'bg-green-600' : 'bg-red-600' }}">
                {{ $delen[0]->actief ? 'Actief' : 'Inactief' }}
            </div>

            {{-- Date box --}}
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

<section class="mb-4 p-4 border rounded bg-gray-50 text-gray-800">
    <div id="deel-beschrijving">
        {{ $delen[0]->description ?? '' }}
    </div>
</section>

{{-- Forms and buttons --}}
<div class="flex justify-between mt-4 space-x-4" id="form-container">
    <!-- Form will be dynamically updated by JavaScript -->
    
    @if(auth()->user()->role === 'student')
        @if(auth()->user()->student->bevestigdeKeuzedelen()->count() > 0)
            <a href="{{ route('more-options.index') }}" 
               class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded">
                📋 Keuzes Opgeven
            </a>
        @endif
    @endif

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
                <form method="POST" action="{{ route('inschrijven.store') }}" id="enrollment-form">
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
            <a href="{{ route('keuzedeel.edit', $deel->id) }}"
               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded inline-block mt-2">
                Pas info aan
            </a>
        @endif
    </div>
    @endforeach
</div>

{{-- Actief modal --}}
<div id="actief-modal"
     class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded shadow-lg p-6 w-96">
        <h2 class="text-xl font-bold mb-4">Actief status wijzigen</h2>
        <p class="mb-2">Weet je zeker dat je de actief-status wilt wijzigen? LET OP! Hierdoor zal de inschrijvingsstatus aangepast worden naar afgewezen! (functie moet nog gemaakt worden)</p>

        <div class="mb-4 text-sm bg-gray-100 border rounded p-2">
            <strong>Subdeel ID dat wordt verstuurd:</strong><br>
            <span id="popup-subdeel-id" class="font-mono text-xs break-all text-gray-700">—</span>
        </div>

        <form method="POST" id="actief-form">
            @csrf
            <button type="button" onclick="closeActiefModal()"
                    class="px-4 py-2 border rounded hover:bg-gray-200">Annuleren</button>
            <button type="submit"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">Bevestigen</button>
        </form>
    </div>
</div>

{{-- 3-Choice Modal --}}
<div id="choices-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded shadow-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
        <h2 class="text-2xl font-bold mb-4">📋 Maak je 3 keuzes</h2>
        <p class="text-gray-600 mb-6">Voordat je je kunt inschrijven, moet je 3 keuzes opgeven (1e, 2e, en 3e keuze).</p>
        
        <form method="POST" action="{{ route('more-options.store') }}" id="choices-form">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                {{-- 1e Keuze --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        1e Keuze <span class="text-red-500">*</span>
                    </label>
                    <select name="first_choice" id="first_choice" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Kies een keuzedeel...</option>
                    </select>
                </div>
                
                {{-- 2e Keuze --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        2e Keuze <span class="text-red-500">*</span>
                    </label>
                    <select name="second_choice" id="second_choice" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Kies een keuzedeel...</option>
                    </select>
                </div>
                
                {{-- 3e Keuze --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        3e Keuze <span class="text-red-500">*</span>
                    </label>
                    <select name="third_choice" id="third_choice" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Kies een keuzedeel...</option>
                    </select>
                </div>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-6">
                <p class="text-sm text-blue-800">
                    <strong>Let op:</strong> Geselecteerde keuzedelen worden automatisch uit de andere dropdowns verwijderd om dubbele selecties te voorkomen.
                </p>
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeChoicesModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Annuleren
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Keuzes Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

@php
$js_ids = $delen->pluck('id');
$js_ingeschreven = $delen->pluck('ingeschreven_count');
$js_beschrijvingen = $delen->pluck('description');
$js_actief = $delen->pluck('actief');
$js_start = $delen->pluck('start_inschrijving');
$js_eind = $delen->pluck('eind_inschrijving');
$js_maximum = $delen->pluck('maximum_studenten');
@endphp

<script>
const deelButtons = document.querySelectorAll('.deel-btn');
const signedUpDisplay = document.querySelector('#aantal-ingeschreven span');
const titelSpan = document.getElementById('huidig-deel-titel');
const descriptionSection = document.getElementById('deel-beschrijving');
const actiefBox = document.getElementById('actief-status-box');
const datumBox = document.getElementById('datum-box');
const modal = document.getElementById('actief-modal');
const modalIdSpan = document.getElementById('popup-subdeel-id');
const actiefForm = document.getElementById('actief-form');

const ids = @json($js_ids);
const aantallen = @json($js_ingeschreven);
const beschrijvingen = @json($js_beschrijvingen);
const actief = @json($js_actief);
const startData = @json($js_start);
const eindData = @json($js_eind);
const maximums = @json($js_maximum);

function selectDeelById(id) {
    deelButtons.forEach((btn, i) => {
        const active = btn.dataset.id === id;
        btn.classList.toggle('opacity-100', active);
        btn.classList.toggle('opacity-60', !active);

        if (active) {
            signedUpDisplay.textContent = aantallen[i] ?? 0;
            titelSpan.textContent = ids[i] ?? '';
            descriptionSection.textContent = beschrijvingen[i] ?? '';

            actiefBox.textContent = actief[i] ? 'Actief' : 'Inactief';
            actiefBox.className =
                'px-4 py-2 rounded text-white font-semibold flex items-center ' +
                (actief[i] ? 'bg-green-600' : 'bg-red-600');

                function formatDateDMY(dateStr) {
                    if (!dateStr) return '';
                    const [year, month, day] = dateStr.split('-');
                    return `${day}-${month}-${year}`;
                }

                datumBox.innerHTML = `
                    <div><p>Inschrijvingsperiode:</p></div>
                    <div>${formatDateDMY(startData[i])} / ${formatDateDMY(eindData[i])}</div>
                `;


            modalIdSpan.textContent = id;
            actiefForm.action = `/keuzedeel/${id}/toggle-actief`;

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
    return new URLSearchParams(window.location.search).get(param);
}

const sessionSubdeel = @json(session('subdeel_id'));
const initialDeel =
    (sessionSubdeel && ids.includes(sessionSubdeel)) ? sessionSubdeel :
    (getQueryParam('id') && ids.includes(getQueryParam('id'))) ? getQueryParam('id') :
    (ids.length > 0 ? ids[0] : null);

if (initialDeel) selectDeelById(initialDeel);

deelButtons.forEach(btn => {
    btn.addEventListener('click', () => selectDeelById(btn.dataset.id));
});

function openActiefModal() {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeActiefModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// 3-Choice Modal Functions
function openChoicesModal() {
    document.getElementById('choices-modal').classList.remove('hidden');
    document.getElementById('choices-modal').classList.add('flex');
    loadAvailableKeuzedelen();
}

function closeChoicesModal() {
    document.getElementById('choices-modal').classList.add('hidden');
    document.getElementById('choices-modal').classList.remove('flex');
}

function loadAvailableKeuzedelen() {
    // Load available keuzedelen via AJAX
    fetch('{{ route("more-options.index") }}')
        .then(response => response.text())
        .then(html => {
            // Extract keuzedelen data from the response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const options = doc.querySelectorAll('select option');
            
            const selects = ['first_choice', 'second_choice', 'third_choice'];
            selects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    // Clear existing options except the first one
                    select.innerHTML = '<option value="">Kies een keuzedeel...</option>';
                    
                    // Add available options
                    options.forEach(option => {
                        if (option.value) {
                            const newOption = document.createElement('option');
                            newOption.value = option.value;
                            newOption.textContent = option.textContent;
                            select.appendChild(newOption);
                        }
                    });
                }
            });
        })
        .catch(error => console.error('Error loading keuzedelen:', error));
}

// Handle choices form submission
document.addEventListener('DOMContentLoaded', function() {
    const choicesForm = document.getElementById('choices-form');
    if (choicesForm) {
        choicesForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Close modal and show success
                    closeChoicesModal();
                    
                    // Show success message
                    const successDiv = document.createElement('div');
                    successDiv.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4';
                    successDiv.innerHTML = `<strong>${data.message}</strong>`;
                    
                    // Insert at the top of the main content
                    const mainContent = document.querySelector('main') || document.body;
                    mainContent.insertBefore(successDiv, mainContent.firstChild);
                    
                    // Remove after 5 seconds
                    setTimeout(() => successDiv.remove(), 5000);
                    
                    // Reload page after a short delay to update enrollment status
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback to normal form submission
                this.submit();
            });
        });
    }
});

// Handle enrollment form submission
document.addEventListener('DOMContentLoaded', function() {
    const enrollmentForm = document.getElementById('enrollment-form');
    if (enrollmentForm) {
        enrollmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'needs_choices') {
                    // Show the choices modal
                    openChoicesModal();
                } else if (data.redirect) {
                    // Redirect on success
                    window.location.href = data.redirect;
                } else {
                    // Reload page on success
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Fallback to normal form submission
                this.submit();
            });
        });
    }
});

// Prevent duplicate selections
document.addEventListener('DOMContentLoaded', function() {
    const selects = ['first_choice', 'second_choice', 'third_choice'];
    
    selects.forEach(function(selectId) {
        const select = document.getElementById(selectId);
        if (select) {
            select.addEventListener('change', function() {
                const selectedValue = this.value;
                
                // Update all other selects to hide/disable the selected option
                selects.forEach(function(otherId) {
                    if (otherId !== selectId) {
                        const otherSelect = document.getElementById(otherId);
                        
                        // Re-enable all options first
                        Array.from(otherSelect.options).forEach(option => {
                            if (option.value !== '') {
                                option.disabled = false;
                                option.style.display = 'block';
                            }
                        });
                        
                        // Then disable the currently selected option in other selects
                        if (selectedValue !== '') {
                            Array.from(otherSelect.options).forEach(option => {
                                if (option.value === selectedValue) {
                                    option.disabled = true;
                                    option.style.display = 'none';
                                }
                            });
                        }
                        
                        // If the other select had the same value selected, clear it
                        if (otherSelect.value === selectedValue) {
                            otherSelect.value = '';
                        }
                    }
                });
            });
        }
    });
    
    // Initial setup to disable already selected options
    selects.forEach(function(selectId) {
        const select = document.getElementById(selectId);
        if (select && select.value !== '') {
            // Trigger change event to apply the logic
            select.dispatchEvent(new Event('change'));
        }
    });
});

</script>

@endsection
