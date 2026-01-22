@extends('layouts.app')

@section('title', 'Keuzedeel aanpassen')

@section('content')
<main class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">
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

    {{-- Form fields --}}
    <form method="POST" action="{{ route('keuzedeel.update', $keuzedeel->id) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-medium mb-1">Titel</label>
            <input
                type="text"
                name="title"
                value="{{ old('title', $keuzedeel->title) }}"
                class="w-full border rounded px-3 py-2"
                required
            >
            <p class="text-sm text-gray-500 mt-1">
                Deze titel wordt aangepast voor alle subdelen onder dit hoofdkeuzedeel.
            </p>
        </div>

        <div>
            <label class="block font-medium mb-1">Beschrijving</label>
            <textarea
                name="description"
                rows="4"
                class="w-full border rounded px-3 py-2"
                required
            >{{ old('description', $keuzedeel->description) }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
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
        </div>

        {{-- Buttons --}}
        <div class="flex gap-4 mt-4">
            <button
                type="submit"
                class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
                Opslaan
            </button>

            <button
                type="button"
                onclick="openDeleteModal()"
                class="flex-1 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
            >
                Verwijderen
            </button>
        </div>
    </form>

    {{-- Delete confirmation modal --}}
    <div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded shadow-lg p-6 max-w-sm w-full text-center">
            <h2 class="text-xl font-bold mb-4">Let op!</h2>
            <p class="mb-4 text-gray-700">
                Dit zal het keuzedeel verwijderen. Als dit het laatste subdeel is, wordt ook het hoofdkeuzedeel verwijderd!
            </p>

            <div class="flex gap-4 justify-center">
                <button onclick="closeDeleteModal()" class="px-4 py-2 hover:bg-gray-200border rounded">
                    Annuleren
                </button>

                <form method="POST" action="{{ route('keuzedeel.destroy', $keuzedeel->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                        Bevestigen
                    </button>
                </form>
            </div>
        </div>
    </div>

</main>

<script>
function openDeleteModal() {
    document.getElementById('delete-modal').classList.remove('hidden');
    document.getElementById('delete-modal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.add('hidden');
    document.getElementById('delete-modal').classList.remove('flex');
}
</script>
@endsection
