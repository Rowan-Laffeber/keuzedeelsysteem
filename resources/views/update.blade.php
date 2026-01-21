@extends('layouts.app')

@section('title', 'Keuzedeel aanpassen')

@section('content')
<main class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">
        Keuzedeel aanpassen – {{ $keuzedeel->id }}
    </h1>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('keuzedeel.update', $keuzedeel->id) }}" class="space-y-4">
        @csrf
        @method('PUT')

        {{-- Titel --}}
        <div>
            <label class="block font-medium mb-1">Titel</label>
            <input
                type="text"
                name="title"
                value="{{ old('title', $keuzedeel->title) }}"
                class="w-full border rounded px-3 py-2"
                required
            >
            <p class="text-sm text-gray-500">
                Deze titel wordt aangepast voor alle subdelen onder dit hoofdkeuzedeel.
            </p>
        </div>

        {{-- Beschrijving --}}
        <div>
            <label class="block font-medium mb-1">Beschrijving</label>
            <textarea
                name="description"
                rows="4"
                class="w-full border rounded px-3 py-2"
                required
            >{{ old('description', $keuzedeel->description) }}</textarea>
        </div>

        {{-- Inschrijfdata --}}
        <div>
            <label class="block font-medium mb-1">Start inschrijving</label>
            <input
                type="date"
                name="start_inschrijving"
                value="{{ old('start_inschrijving', $keuzedeel->start_inschrijving) }}"
                class="w-full border rounded px-3 py-2"
                required
            >
        </div>

        <div>
            <label class="block font-medium mb-1">Eind inschrijving</label>
            <input
                type="date"
                name="eind_inschrijving"
                value="{{ old('eind_inschrijving', $keuzedeel->eind_inschrijving) }}"
                class="w-full border rounded px-3 py-2"
                required
            >
        </div>

        <button
            type="submit"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Opslaan
        </button>
    </form>
</main>
@endsection
