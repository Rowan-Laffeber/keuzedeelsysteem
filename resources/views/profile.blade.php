@extends('layouts.app')

@section('title', 'Mijn Profiel - Keuzedeel Systeem')

@section('content')

<main class="max-w-7xl mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Mijn Profiel</h1>
        <h2 class="text-2xl font-bold text-gray-900">{{ $student->user->name }}</h2>
        <p class="text-gray-600 mt-2">Hier zie je je ingeschreven keuzedelen.</p>
    </div>

    <!-- Student Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Studentgegevens</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><span class="font-medium">Roostergroep:</span> {{ $student->roostergroep }}</div>
            <div><span class="font-medium">Studentnummer:</span> {{ $student->studentnummer }}</div>
            <div><span class="font-medium">Opleidingsnummer:</span> {{ $student->opleidingsnummer }}</div>
            <div><span class="font-medium">Cohort:</span> {{ $student->cohort_year }}</div>
        </div>
    </div>

    <!-- Ingeschreven Keuzedelen -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold">Ingeschreven Keuzedelen ({{ $ingeschrevenKeuzedelen->count() }})</h2>
            @if($ingeschrevenKeuzedelen->count() > 1)
                <button onclick="openPriorityModal()" 
                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm font-medium">
                    Update Priorities
                </button>
            @endif
        </div>

        @if($ingeschrevenKeuzedelen->count() > 0)
            <div class="space-y-4">
                @php
                    // Sort by priority ascending (laag → hoog)
                    $sortedKeuzedelen = $ingeschrevenKeuzedelen->sortBy(function($k) {
                        return $k->pivot->priority ?? 999; // fallback high number if null
                    });

                    // Function to get status badge color
                    function statusColor($status) {
                        return match($status) {
                            'goedgekeurd' => 'bg-green-100 text-green-800',
                            'ingediend' => 'bg-yellow-100 text-yellow-800',
                            'afgewezen' => 'bg-red-100 text-red-800',
                            'afgerond' => 'bg-blue-100 text-blue-800',
                            default => 'bg-gray-100 text-gray-800',
                        };
                    }
                @endphp

                @foreach($sortedKeuzedelen as $keuzedeel)
                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900">{{ $keuzedeel->title }}</h3>
                                
                                @if($keuzedeel->parent)
                                    <p class="text-sm text-gray-600 mb-2">
                                        Onderdeel van: {{ $keuzedeel->parent->title }}
                                    </p>
                                @endif
                                
                                <p class="text-gray-700">{{ Str::limit($keuzedeel->description, 150) }}</p>

                                @if($keuzedeel->pivot)
                                    <div class="flex flex-wrap gap-2 mt-2 text-sm">
                                        @if($keuzedeel->pivot->inschrijfdatum)
                                            <span class="text-gray-500">
                                                Ingeschreven op: 
                                                {{ is_string($keuzedeel->pivot->inschrijfdatum) 
                                                    ? \Carbon\Carbon::parse($keuzedeel->pivot->inschrijfdatum)->format('d-m-Y H:i') 
                                                    : $keuzedeel->pivot->inschrijfdatum->format('d-m-Y H:i') }}
                                            </span>
                                        @endif

                                        @if(isset($keuzedeel->pivot->priority))
                                            <span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800 font-medium">
                                                Prio: {{ $keuzedeel->pivot->priority }}
                                            </span>
                                        @endif

                                        @if(isset($keuzedeel->pivot->status))
                                            <span class="px-2 py-1 rounded font-medium {{ statusColor($keuzedeel->pivot->status) }}">
                                                {{ ucfirst($keuzedeel->pivot->status) }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div class="ml-4">
                                <a href="{{ route('keuzedeel.info', $keuzedeel->id) }}"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                    Bekijk Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <div class="text-gray-400 text-6xl mb-4">📚</div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nog geen keuzedelen ingeschreven</h3>
                <p class="text-gray-600 mb-4">Je hebt je nog niet ingeschreven voor keuzedelen.</p>
                <a href="{{ route('home') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                    Bekijk Beschikbare Keuzedelen
                </a>
            </div>
        @endif
    </div>

    {{-- Priority Update Modal --}}
    <div id="priority-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded shadow-lg p-6 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
            <h2 class="text-xl font-bold mb-4">Update Priorities</h2>
            <p class="text-gray-600 mb-4">Kies je nieuwe prioriteiten voor je ingeschreven keuzedelen.</p>

            <form id="priority-form" method="POST" action="{{ route('priorities.update') }}">
                @csrf
                @method('PUT')
                
                <div class="space-y-4">
                    @foreach($ingeschrevenKeuzedelen as $index => $keuzedeel)
                        <div class="border rounded-lg p-4">
                            <h3 class="font-semibold text-gray-900 mb-2">{{ $keuzedeel->title }}</h3>
                            <p class="text-sm text-gray-600 mb-3">{{ Str::limit($keuzedeel->description, 100) }}</p>
                            
                            <input type="hidden" name="keuzedeel_ids[]" value="{{ $keuzedeel->id }}">
                            
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nieuwe Prioriteit:
                            </label>
                            <select name="priorities[]" required class="border rounded w-full px-3 py-2">
                                <option value="">Kies prioriteit</option>
                                <option value="1" {{ ($keuzedeel->pivot->priority ?? 999) == 1 ? 'selected' : '' }}>1e Keuze</option>
                                <option value="2" {{ ($keuzedeel->pivot->priority ?? 999) == 2 ? 'selected' : '' }}>2e Keuze</option>
                                <option value="3" {{ ($keuzedeel->pivot->priority ?? 999) == 3 ? 'selected' : '' }}>3e Keuze</option>
                            </select>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="closePriorityModalWithClear()"
                            class="border px-4 py-2 rounded">Annuleren</button>
                    <button type="submit"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">
                        Opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPriorityModal() {
            document.getElementById('priority-modal').classList.remove('hidden');
            document.getElementById('priority-modal').classList.add('flex');
        }

        function closePriorityModal() {
            document.getElementById('priority-modal').classList.add('hidden');
            document.getElementById('priority-modal').classList.remove('flex');
        }

        // Prevent duplicate priority selections with error message
        document.getElementById('priority-form')?.addEventListener('change', function(e) {
            if (e.target.name === 'priorities[]') {
                const selectedPriority = e.target.value;
                const allSelects = document.querySelectorAll('select[name="priorities[]"]');
                
                // Check if this priority is already selected elsewhere
                let isDuplicate = false;
                allSelects.forEach(select => {
                    if (select !== e.target && select.value === selectedPriority) {
                        isDuplicate = true;
                    }
                });

                if (isDuplicate && selectedPriority !== '') {
                    // Show error message
                    const errorMsg = `Je hebt al prio ${selectedPriority} gekozen`;
                    
                    // Create or update error message
                    let errorDiv = document.getElementById('priority-error');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.id = 'priority-error';
                        errorDiv.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
                        e.target.closest('.space-y-4').parentNode.insertBefore(errorDiv, e.target.closest('.space-y-4'));
                    }
                    errorDiv.textContent = errorMsg;
                    
                    // Reset the selection
                    e.target.value = '';
                    return;
                }

                // Remove error message if it exists
                const errorDiv = document.getElementById('priority-error');
                if (errorDiv) {
                    errorDiv.remove();
                }
                
                // Disable/enable options to prevent duplicates
                allSelects.forEach(select => {
                    if (select !== e.target) {
                        Array.from(select.options).forEach(option => {
                            if (option.value === selectedPriority) {
                                option.disabled = true;
                            } else {
                                option.disabled = false;
                            }
                        });
                    }
                });
            }
        });

        // Clear error when modal is closed
        function closePriorityModalWithClear() {
            const errorDiv = document.getElementById('priority-error');
            if (errorDiv) {
                errorDiv.remove();
            }
            closePriorityModal();
        }
    </script>
</main>

@endsection
