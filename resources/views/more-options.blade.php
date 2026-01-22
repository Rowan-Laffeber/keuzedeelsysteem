@extends('layouts.app')

@section('title', 'Keuzes Opgeven - Keuzedeel Systeem')

@section('content')
<main class="max-w-4xl mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Keuzes Opgeven</h1>
        <p class="text-gray-600 mt-2">Geef je 1e, 2e en 3e keuze op voor de keuzedelen.</p>
        
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mt-4">
                {{ session('success') }}
            </div>
        @endif
    </div>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('more-options.store') }}">
            @csrf
            
            <!-- First Choice -->
            <div class="mb-6">
                <label for="first_choice" class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="text-lg font-bold">1️⃣ Eerste Keuze</span>
                    <span class="text-sm text-gray-500 block">Je voorkeur als er genoeg plaatsen zijn</span>
                </label>
                <select 
                    id="first_choice" 
                    name="first_choice" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                    <option value="">Kies een keuzedeel...</option>
                    @foreach($availableKeuzedelen as $keuzedeel)
                        <option 
                            value="{{ $keuzedeel->id }}" 
                            {{ $choices->has(1) && $choices[1]->keuzedeel_id === $keuzedeel->id ? 'selected' : '' }}
                        >
                            {{ $keuzedeel->title }} 
                            (Min: {{ $keuzedeel->minimum_studenten ?? 1 }}, Max: {{ $keuzedeel->maximum_studenten ?? 30 }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Second Choice -->
            <div class="mb-6">
                <label for="second_choice" class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="text-lg font-bold">2️⃣ Tweede Keuze</span>
                    <span class="text-sm text-gray-500 block">Als je eerste keuze niet doorgaat</span>
                </label>
                <select 
                    id="second_choice" 
                    name="second_choice" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                    <option value="">Kies een keuzedeel...</option>
                    @foreach($availableKeuzedelen as $keuzedeel)
                        <option 
                            value="{{ $keuzedeel->id }}" 
                            {{ $choices->has(2) && $choices[2]->keuzedeel_id === $keuzedeel->id ? 'selected' : '' }}
                        >
                            {{ $keuzedeel->title }} 
                            (Min: {{ $keuzedeel->minimum_studenten ?? 1 }}, Max: {{ $keuzedeel->maximum_studenten ?? 30 }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Third Choice -->
            <div class="mb-6">
                <label for="third_choice" class="block text-sm font-medium text-gray-700 mb-2">
                    <span class="text-lg font-bold">3️⃣ Derde Keuze</span>
                    <span class="text-sm text-gray-500 block">Als je tweede keuze ook niet doorgaat</span>
                </label>
                <select 
                    id="third_choice" 
                    name="third_choice" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                    <option value="">Kies een keuzedeel...</option>
                    @foreach($availableKeuzedelen as $keuzedeel)
                        <option 
                            value="{{ $keuzedeel->id }}" 
                            {{ $choices->has(3) && $choices[3]->keuzedeel_id === $keuzedeel->id ? 'selected' : '' }}
                        >
                            {{ $keuzedeel->title }} 
                            (Min: {{ $keuzedeel->minimum_studenten ?? 1 }}, Max: {{ $keuzedeel->maximum_studenten ?? 30 }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium">
                    Keuzes Opslaan
                </button>
            </div>
        </form>
    </div>

    <!-- Current Choices Display -->
    @if($choices->count() > 0)
        <div class="bg-white rounded-lg shadow p-6 mt-6">
            <h2 class="text-xl font-semibold mb-4">Je Huidige Keuzes</h2>
            <div class="space-y-3">
                @foreach($choices as $priority => $choice)
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <div>
                            <span class="font-medium">
                                {{ $priority === 1 ? '1️⃣ Eerste' : ($priority === 2 ? '2️⃣ Tweede' : '3️⃣ Derde') }} Keuze:
                            </span>
                            <span class="ml-2 text-gray-700">{{ $choice->keuzedeel->title }}</span>
                            <span class="ml-2 text-sm text-gray-500">
                                (Status: {{ $choice->status }})
                            </span>
                        </div>
                        <div class="text-sm text-gray-500">
                            Min: {{ $choice->keuzedeel->minimum_studenten ?? 1 }} | 
                            Max: {{ $choice->keuzedeel->maximum_studenten ?? 30 }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</main>

<script>
// Prevent selecting the same keuzedeel multiple times
document.addEventListener('DOMContentLoaded', function() {
    const firstChoice = document.getElementById('first_choice');
    const secondChoice = document.getElementById('second_choice');
    const thirdChoice = document.getElementById('third_choice');

    function updateOptions() {
        const selected = [firstChoice.value, secondChoice.value, thirdChoice.value].filter(v => v);
        
        // Update second choice options
        Array.from(secondChoice.options).forEach(option => {
            if (selected.includes(option.value) && option.value !== secondChoice.value) {
                option.disabled = true;
            } else {
                option.disabled = false;
            }
        });

        // Update third choice options
        Array.from(thirdChoice.options).forEach(option => {
            if (selected.includes(option.value) && option.value !== thirdChoice.value) {
                option.disabled = true;
            } else {
                option.disabled = false;
            }
        });
    }

    firstChoice.addEventListener('change', updateOptions);
    secondChoice.addEventListener('change', updateOptions);
    thirdChoice.addEventListener('change', updateOptions);
});
</script>
@endsection
