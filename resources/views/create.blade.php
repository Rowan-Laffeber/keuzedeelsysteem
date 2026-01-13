@extends('layouts.app')

@section('title', 'Keuzedeel Aanmaken')

@section('content')
<main class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Nieuw Keuzedeel Aanmaken</h1>

    {{-- Session messages --}}
    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-4 rounded mb-4">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-4 rounded mb-4">{{ session('success') }}</div>
    @endif

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

    <form action="{{ route('keuzedeel.store') }}" method="POST" class="space-y-4">
        @csrf

        {{-- Parent selection --}}
        <div>
            <label for="parent_id" class="block font-medium mb-1">Selecteer hoofdkeuzedeel (optioneel)</label>
            <select name="parent_id" id="parent_id" class="w-full border rounded px-3 py-2">
                <option value="">Maak een nieuw hoofdkeuzedeel aan</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" data-title="{{ $parent->title }}"
                        {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                        {{ $parent->title }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Title input --}}
        <div>
            <label for="title" class="block font-medium mb-1">Titel van het Keuzedeel</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}"
                   class="w-full border rounded px-3 py-2" required>
            <p class="text-sm text-gray-500">
                Wordt automatisch gebruikt voor subdelen als hoofdkeuzedeel geselecteerd is.
            </p>
        </div>

        {{-- Keuzedeelnummer --}}
        <div>
            <label for="id_select" class="block font-medium mb-1">Keuzedeelnummer</label>
            <select id="id_select" class="w-full border rounded px-3 py-2" required>
                <option value="" selected>-- Selecteer keuzedeel nummer --</option>
                @foreach($keuzedelen as $k)
                    <option value="{{ $k }}">{{ $k }}</option>
                @endforeach
                <option value="manual">Handmatig invoeren</option>
            </select>

            <input type="text" name="id" id="id_manual" class="w-full border rounded px-3 py-2 mt-2"
                   placeholder="Keuzedeelnummer handmatig invoeren" style="display:none;" value="{{ old('id') }}">
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="block font-medium mb-1">Beschrijving</label>
            <textarea name="description" id="description" rows="4" required
                      class="w-full border rounded px-3 py-2">{{ old('description') }}</textarea>
        </div>

        {{-- Parent max students --}}
        <div>
            <label for="parent_max" class="block font-medium mb-1">Max studenten voor nieuwe hoofdkeuzedeel</label>
            <input type="number" name="parent_max" id="parent_max" value="{{ old('parent_max', 30) }}"
                   class="w-full border rounded px-3 py-2" min="1" max="100">
            <p class="text-sm text-gray-500">Alleen relevant als er geen hoofdkeuzedeel wordt geselecteerd.</p>
        </div>

        {{-- Deel max students --}}
        <div>
            <label for="deel_max" class="block font-medium mb-1">Max studenten voor dit deel</label>
            <input type="number" name="deel_max" id="deel_max" value="{{ old('deel_max', 30) }}"
                   class="w-full border rounded px-3 py-2" min="1" max="100" required>
        </div>

        {{-- Start and end inschrijving --}}
        <div>
            <label for="start_inschrijving" class="block font-medium mb-1">Start inschrijving</label>
            <input type="date" name="start_inschrijving" id="start_inschrijving"
                   value="{{ old('start_inschrijving') }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div>
            <label for="eind_inschrijving" class="block font-medium mb-1">Eind inschrijving</label>
            <input type="date" name="eind_inschrijving" id="eind_inschrijving"
                   value="{{ old('eind_inschrijving') }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Aanmaken
        </button>
    </form>
</main>

<script>
// Keuzedeelnummer dropdown/manual input logic
const selectId = document.getElementById('id_select');
const manualInput = document.getElementById('id_manual');

selectId.addEventListener('change', () => {
    if (selectId.value === 'manual') {
        manualInput.style.display = 'block';
        manualInput.setAttribute('name', 'id');
        selectId.removeAttribute('name');
        manualInput.required = true;
    } else {
        manualInput.style.display = 'none';
        manualInput.removeAttribute('name');
        selectId.setAttribute('name', 'id');
        manualInput.required = false;
    }
});

window.addEventListener('DOMContentLoaded', () => {
    const oldId = "{{ old('id') }}";
    if (oldId && ![...selectId.options].some(opt => opt.value === oldId)) {
        selectId.value = 'manual';
        manualInput.style.display = 'block';
        manualInput.value = oldId;
        manualInput.setAttribute('name', 'id');
        selectId.removeAttribute('name');
        manualInput.required = true;
    } else {
        selectId.value = oldId || '';
        manualInput.style.display = 'none';
        manualInput.removeAttribute('name');
        selectId.setAttribute('name', 'id');
        manualInput.required = false;
    }

    // Parent selection -> autofill title & lock
    const parentSelect = document.getElementById('parent_id');
    const titleInput = document.getElementById('title');

    const updateTitle = () => {
        const selectedOption = parentSelect.options[parentSelect.selectedIndex];
        if (selectedOption.value !== "") {
            titleInput.value = selectedOption.dataset.title;
            titleInput.readOnly = true;
            titleInput.classList.add('bg-gray-100');
        } else {
            titleInput.readOnly = false;
            titleInput.classList.remove('bg-gray-100');
            titleInput.value = "{{ old('title') }}"; // restore old input if exists
        }
    };

    parentSelect.addEventListener('change', updateTitle);
    updateTitle();
});
</script>
@endsection
