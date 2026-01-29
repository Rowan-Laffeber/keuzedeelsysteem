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
    <div class="mb-2 grid grid-cols-1 sm:grid-cols-5 gap-4">
        <input
            id="searchInput"
            type="text"
            placeholder="Zoek op naam, studentnummer of keuzedeel..."
            class="border rounded px-3 py-2 w-full"
        />

        <select id="keuzedeelFilter" class="border rounded px-3 py-2">
            <option value="">Alle keuzedelen</option>
            @foreach($keuzedelen as $keuzedeel)
                <option value="{{ $keuzedeel->id }}">{{ $keuzedeel->id }}</option>
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

        <button id="resetFilters" class="bg-gray-500 text-white px-4 py-2 rounded font-semibold">
            Reset
        </button>
    </div>

    {{-- Result count --}}
    <div id="resultsCount" class="mb-4 text-gray-700 font-semibold">
        {{ $students->count() }} resultaten
    </div>

    {{-- Students header --}}
    <div class="hidden sm:flex font-semibold text-gray-700 px-4 py-2 bg-gray-100 border-b">
        <div class="w-1/4">Naam</div>
        <div class="w-1/6">Studentnummer</div>
        <div class="w-1/6">Opleidingsnummer</div>
        <div class="w-1/6">Cohort</div>
        <div class="w-1/6">Roostergroep</div>
        <div class="w-1/6">Inschrijvingen</div>
    </div>

    {{-- Students container --}}
    <div id="studentsContainer" class="divide-y divide-gray-200">

        @forelse($students as $student)
            <div class="student-row"
                 data-name="{{ strtolower($student->user->name ?? '') }}"
                 data-studentnummer="{{ $student->studentnummer }}"
                 data-roostergroep="{{ $student->roostergroep }}"
                 data-opleiding="{{ $student->opleidingsnummer }}">

                {{-- Main row --}}
                <div class="flex flex-col sm:flex-row items-start sm:items-center px-4 py-3 hover:bg-gray-50">
                    <div class="w-full sm:w-1/4 font-semibold">{{ $student->user->name ?? 'Naam onbekend' }}</div>
                    <div class="w-full sm:w-1/6">{{ $student->studentnummer }}</div>
                    <div class="w-full sm:w-1/6">{{ $student->opleidingsnummer }}</div>
                    <div class="w-full sm:w-1/6">{{ $student->cohort_year }}</div>
                    <div class="w-full sm:w-1/6">{{ $student->roostergroep }}</div>
                    <div class="w-full sm:w-1/6 mt-2 sm:mt-0">
                        @if($student->inschrijvingen->count())
                        <button class="toggle-inschrijvingen bg-blue-600 text-white px-4 py-1 rounded font-semibold w-full">
                            Toon inschrijvingen
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Inschrijvingen --}}
                @if($student->inschrijvingen->count())
                <div class="inschrijvingen-container max-h-0 overflow-hidden transition-all duration-300 bg-gray-50 border-l-4 border-blue-200">
                    <div class="flex font-semibold px-4 py-2 bg-gray-100 border-b">
                        <div class="w-1/4">Keuzedeel</div>
                        <div class="w-1/6">Code</div>
                        <div class="w-1/6">Prioriteit</div>
                        <div class="w-1/6">Status</div>
                        <div class="w-1/6">Functies</div>
                        <div class="w-1/6"></div> {{-- Lege div voor styling --}}
                    </div>

                    @foreach($student->inschrijvingen as $inschrijving)
                    <div class="flex px-4 py-2 items-center border-b last:border-b-0"
                         data-keuzedeel="{{ $inschrijving->keuzedeel_id }}"
                         data-keuzedeel-title="{{ strtolower($inschrijving->keuzedeel->title ?? '') }}">
                        <div class="w-1/4">{{ $inschrijving->keuzedeel->title }}</div>
                        <div class="w-1/6">{{ $inschrijving->keuzedeel_id }}</div>
                        <div class="w-1/6">{{ $inschrijving->priority }}</div>
                        @php
                            $statusColors = [
                                'ingediend'   => 'text-yellow-600',
                                'goedgekeurd' => 'text-green-600',
                                'afgewezen'   => 'text-red-600',
                                'afgerond'    => 'text-blue-600',
                            ];

                            $statusClass = $statusColors[$inschrijving->status] ?? 'text-gray-600';
                        @endphp

                        <div class="w-1/6 font-semibold {{ $statusClass }}">
                            {{ ucfirst($inschrijving->status) }}
                        </div>

                        <div class="w-1/6">
                            <form method="POST" action="{{ route('uitschrijven.destroy') }}" onsubmit="return confirm('Weet je zeker dat je deze inschrijving wilt verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="keuzedeel_id" value="{{ $inschrijving->keuzedeel_id }}">
                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded font-semibold">
                                    Verwijderen
                                </button>
                            </form>
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
                @endif

            </div>
        @empty
            <div class="px-4 py-6 text-gray-500 text-center" id="noResultsStatic">
                Geen studenten gevonden.
            </div>
        @endforelse

        <div id="noResults" class="px-4 py-6 text-gray-500 text-center" style="display:none;">
            Geen studenten gevonden.
        </div>

    </div>
    <div class="flex flex-col sm:flex-row items-center justify-between mt-6 px-4 sm:px-0">
        {{-- Results summary --}}
        <div class="text-gray-700 text-sm mb-2 sm:mb-0">
            Showing {{ $students->firstItem() }} to {{ $students->lastItem() }} of {{ $students->total() }} results
        </div>

        {{-- Pagination --}}
        <nav class="inline-flex -space-x-px" aria-label="Pagination">
            {{-- Previous --}}
            @if($students->onFirstPage())
                <span class="px-3 py-1 rounded-l-md border border-gray-300 bg-gray-100 text-gray-400 cursor-not-allowed">Previous</span>
            @else
                <a href="{{ $students->previousPageUrl() }}" class="px-3 py-1 rounded-l-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Previous</a>
            @endif

            {{-- Page numbers --}}
            @foreach ($students->getUrlRange(1, $students->lastPage()) as $page => $url)
                @if ($page == $students->currentPage())
                    <span class="px-3 py-1 border border-gray-300 bg-blue-600 text-white">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="px-3 py-1 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if($students->hasMorePages())
                <a href="{{ $students->nextPageUrl() }}" class="px-3 py-1 rounded-r-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Next</a>
            @else
                <span class="px-3 py-1 rounded-r-md border border-gray-300 bg-gray-100 text-gray-400 cursor-not-allowed">Next</span>
            @endif
        </nav>
    </div>


</div>

{{-- JS Filters & URL sync --}}
<script>
const searchInput = document.getElementById('searchInput');
const keuzedeelFilter = document.getElementById('keuzedeelFilter');
const roostergroepFilter = document.getElementById('roostergroepFilter');
const opleidingFilter = document.getElementById('opleidingFilter');
const resetBtn = document.getElementById('resetFilters');
const resultsCount = document.getElementById('resultsCount');

function applyFilters() {
    const search = searchInput.value.toLowerCase();
    const keuzedeel = keuzedeelFilter.value;
    const roostergroep = roostergroepFilter.value;
    const opleiding = opleidingFilter.value;

    let anyVisible = false;
    let visibleCount = 0;

    document.querySelectorAll('.student-row').forEach(student => {
        let visible = true;

        if (roostergroep && student.dataset.roostergroep !== roostergroep) visible = false;
        if (opleiding && student.dataset.opleiding !== opleiding) visible = false;

        if (search && !(
            student.dataset.name.includes(search) ||
            student.dataset.studentnummer.includes(search) ||
            Array.from(student.querySelectorAll('[data-keuzedeel-title]'))
                .some(row => row.dataset.keuzedeelTitle.includes(search))
        )) visible = false;

        if (keuzedeel) {
            const hasKeuzedeel = Array.from(student.querySelectorAll('[data-keuzedeel]'))
                .some(row => row.dataset.keuzedeel === keuzedeel);
            if (!hasKeuzedeel) visible = false;
        }

        student.style.display = visible ? '' : 'none';
        if (visible) {
            anyVisible = true;
            visibleCount++;
        }
    });

    // Update result count
    resultsCount.textContent = `${visibleCount} ${visibleCount === 1 ? 'resultaat' : 'resultaten'}`;

    // Show/hide “geen resultaten”
    document.getElementById('noResults').style.display = anyVisible ? 'none' : '';
    if (document.getElementById('noResultsStatic')) {
        document.getElementById('noResultsStatic').style.display = 'none';
    }

    // Update URL 
    const params = new URLSearchParams();
    if (searchInput.value) params.set('search', searchInput.value);
    if (keuzedeel) params.set('keuzedeel', keuzedeel);
    if (roostergroep) params.set('roostergroep', roostergroep);
    if (opleiding) params.set('opleiding', opleiding);
    const queryString = params.toString();
    window.history.replaceState({}, '', queryString ? `${window.location.pathname}?${queryString}` : window.location.pathname);
}

// Event listeners
searchInput.addEventListener('input', applyFilters);
[keuzedeelFilter, roostergroepFilter, opleidingFilter].forEach(el => el.addEventListener('change', applyFilters));

resetBtn.addEventListener('click', () => {
    searchInput.value = '';
    keuzedeelFilter.selectedIndex = 0;
    roostergroepFilter.selectedIndex = 0;
    opleidingFilter.selectedIndex = 0;
    applyFilters();
});

// Toggle inschrijvingen
document.querySelectorAll('.toggle-inschrijvingen').forEach(btn => {
    btn.onclick = () => {
        const currentContainer = btn.closest('.student-row').querySelector('.inschrijvingen-container');

        // Collapse any other open containers
        document.querySelectorAll('.inschrijvingen-container').forEach(container => {
            if (container !== currentContainer) {
                container.style.maxHeight = null;
            }
        });

        // Toggle current container
        currentContainer.style.maxHeight = currentContainer.style.maxHeight 
            ? null 
            : currentContainer.scrollHeight + 'px';
    };
});


// Initialize filters from URL
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) searchInput.value = urlParams.get('search');
    if (urlParams.has('keuzedeel')) keuzedeelFilter.value = urlParams.get('keuzedeel');
    if (urlParams.has('roostergroep')) roostergroepFilter.value = urlParams.get('roostergroep');
    if (urlParams.has('opleiding')) opleidingFilter.value = urlParams.get('opleiding');
    applyFilters();
});
</script>
@endsection
