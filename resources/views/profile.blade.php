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
                <div>
                    <span class="font-medium">Roostergroep:</span> {{ $student->roostergroep }}
                </div>
                <div>
                    <span class="font-medium">Studentnummer:</span> {{ $student->studentnummer }}
                </div>
                <div>
                    <span class="font-medium">Opleidingsnummer:</span> {{ $student->opleidingsnummer }}
                </div>
                <div>
                    <span class="font-medium">Cohort:</span> {{ $student->cohort_year }}
                </div>
            </div>
        </div>

        <!-- Ingeschreven Keuzedelen -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Ingeschreven Keuzedelen ({{ $ingeschrevenKeuzedelen->count() }})</h2>

            @if($ingeschrevenKeuzedelen->count() > 0)
                <div class="space-y-4">
                    @foreach($ingeschrevenKeuzedelen as $keuzedeel)
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

                                    @if($keuzedeel->pivot && $keuzedeel->pivot->inschrijfdatum)
                                        <p class="text-sm text-gray-500 mt-2">
                                            Ingeschreven op:
                                            {{ is_string($keuzedeel->pivot->inschrijfdatum) ? \Carbon\Carbon::parse($keuzedeel->pivot->inschrijfdatum)->format('d-m-Y H:i') : $keuzedeel->pivot->inschrijfdatum->format('d-m-Y H:i') }}
                                        </p>
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
    </main>

@endsection