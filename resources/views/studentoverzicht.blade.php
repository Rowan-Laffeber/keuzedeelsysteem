@extends('layouts.app')

@section('title', 'Student Overzicht')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-4xl font-bold text-gray-800">Student Overzicht</h1>
        <p class="text-gray-600 mt-1">
            Bekijk alle geregistreerde studenten en hun inschrijvingen.
        </p>
    </div>

    {{-- Filters --}}
    <div class="mb-4 grid grid-cols-1 sm:grid-cols-4 gap-4">
        <input
            id="searchInput"
            type="text"
            placeholder="Zoek op naam, studentnummer of keuzedeel..."
            class="border rounded px-3 py-2 w-full"
        />

        <select id="keuzedeelFilter" class="border rounded px-3 py-2">
            <option value="">Alle keuzedelen</option>
            @foreach($keuzedelen as $keuzedeel)
                @if($keuzedeel->parent_id !== null)
                    <option value="{{ $keuzedeel->id }}">{{ $keuzedeel->id }}</option>
                @endif
            @endforeach
        </select>





        <select id="roostergroepFilter" class="border rounded px-3 py-2">
            <option value="">Alle roostergroepen</option>
            @foreach($roostergroepen as $groep)
                <option value="{{ $groep }}">{{ $groep }}</option>
            @endforeach
        </select>

        <select id="opleidingFilter" class="border rounded px-3 py-2">
            <option value="">Alle opleidingen</option>
            @foreach($opleidingen as $opleiding)
                <option value="{{ $opleiding }}">{{ $opleiding }}</option>
            @endforeach
        </select>
    </div>

    {{-- Students header --}}
    <div class="hidden sm:flex font-semibold text-gray-700 px-4 py-2 bg-gray-100 border-b">
        <div class="w-1/4">Naam</div>
        <div class="w-1/6">Studentnummer</div>
        <div class="w-1/6">Opleidingsnummer</div>
        <div class="w-1/6">Cohort</div>
        <div class="w-1/6">Roostergroep</div>
        <div class="w-1/6">Acties</div>
    </div>

    {{-- Student rows --}}
    <div class="divide-y divide-gray-200">
        @foreach($students as $student)
        <div class="student-row"
             data-name="{{ strtolower($student->user->name ?? '') }}"
             data-studentnummer="{{ $student->studentnummer }}"
             data-roostergroep="{{ $student->roostergroep }}"
             data-opleiding="{{ $student->opleidingsnummer }}">

            {{-- Main row --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center px-4 py-3 hover:bg-gray-50">
                <div class="w-full sm:w-1/4 font-semibold">
                    {{ $student->user->name ?? 'Naam onbekend' }}
                </div>
                <div class="w-full sm:w-1/6">{{ $student->studentnummer }}</div>
                <div class="w-full sm:w-1/6">{{ $student->opleidingsnummer }}</div>
                <div class="w-full sm:w-1/6">{{ $student->cohort_year }}</div>
                <div class="w-full sm:w-1/6">{{ $student->roostergroep }}</div>
                <div class="w-full sm:w-1/6 mt-2 sm:mt-0">
                    <button class="toggle-inschrijvingen bg-blue-600 text-white px-4 py-1 rounded font-semibold w-full">
                        Toon inschrijvingen
                    </button>
                </div>
            </div>

            {{-- Inschrijvingen --}}
            <div class="inschrijvingen-container max-h-0 overflow-hidden transition-all duration-300 bg-gray-50 border-l-4 border-blue-200">
                <div class="flex font-semibold px-4 py-2 bg-gray-100 border-b">
                    <div class="w-1/4">Keuzedeel</div>
                    <div class="w-1/6">Code</div>
                    <div class="w-1/6">Prioriteit</div>
                    <div class="w-1/6">Status</div>
                    <div class="w-1/6">Actie</div>
                    <div class="w-1/6"></div>
                </div>

                @foreach($student->inschrijvingen as $inschrijving)
                <div class="flex px-4 py-2 items-center border-b last:border-b-0"
                     data-keuzedeel="{{ $inschrijving->keuzedeel_id }}"
                     data-keuzedeel-title="{{ strtolower($inschrijving->keuzedeel->title ?? '') }}">
                    <div class="w-1/4">{{ $inschrijving->keuzedeel->title }}</div>
                    <div class="w-1/6">{{ $inschrijving->keuzedeel_id }}</div>
                    <div class="w-1/6">-</div>
                    <div class="w-1/6 text-green-600 font-semibold">
                        {{ ucfirst($inschrijving->status) }}
                    </div>
                    <div class="w-1/6">
                        <button class="bg-yellow-500 text-white px-3 py-1 rounded font-semibold">
                            Wijzig status
                        </button>
                    </div>
                    <div class="w-1/6">
                        <a href="{{ url('/keuzedeel/' . $inschrijving->keuzedeel->parent_id . '?id=' . $inschrijving->keuzedeel->id) }}"
                           class="bg-gray-500 text-white px-3 py-1 rounded font-semibold">
                            Bekijk keuzedeel
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- JS Filters --}}
<script>
const searchInput = document.getElementById('searchInput');
const keuzedeelFilter = document.getElementById('keuzedeelFilter');
const roostergroepFilter = document.getElementById('roostergroepFilter');
const opleidingFilter = document.getElementById('opleidingFilter');

function applyFilters() {
    const search = searchInput.value.toLowerCase();
    const keuzedeel = keuzedeelFilter.value;
    const roostergroep = roostergroepFilter.value;
    const opleiding = opleidingFilter.value;

    document.querySelectorAll('.student-row').forEach(student => {
        let visible = true;

        // Filter by roostergroep / opleiding
        if (roostergroep && student.dataset.roostergroep !== roostergroep) visible = false;
        if (opleiding && student.dataset.opleiding !== opleiding) visible = false;

        // Filter by search input (name or studentnummer)
        if (search && !(student.dataset.name.includes(search) || student.dataset.studentnummer.includes(search))) {
            visible = false;
        }

        // Filter by keuzedeel
        if (keuzedeel) {
            let hasKeuzedeel = false;
            student.querySelectorAll('[data-keuzedeel]').forEach(row => {
                if (row.dataset.keuzedeel === keuzedeel) {
                    hasKeuzedeel = true;
                }
            });
            if (!hasKeuzedeel) visible = false;
        }


        student.style.display = visible ? '' : 'none';
    });
}

// Event listeners
searchInput.addEventListener('input', applyFilters);
[keuzedeelFilter, roostergroepFilter, opleidingFilter].forEach(el => {
    el.addEventListener('change', applyFilters);
});

// Toggle inschrijvingen
document.querySelectorAll('.toggle-inschrijvingen').forEach(btn => {
    btn.onclick = () => {
        const container = btn.closest('.student-row').querySelector('.inschrijvingen-container');
        container.style.maxHeight = container.style.maxHeight ? null : container.scrollHeight + 'px';
    };
});
</script>
@endsection
