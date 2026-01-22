@extends('layouts.app')

@section('title', 'Student Overzicht')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <h1 class="text-4xl font-bold text-gray-800">Student Overzicht</h1>
        <p class="text-gray-600 mt-1">Bekijk alle geregistreerde studenten en hun keuzedelen.</p>
    </div>

    {{-- Header row for students --}}
    <div class="hidden sm:flex font-semibold text-gray-700 px-4 py-2 bg-gray-100 border-b">
        <div class="w-1/4">Naam</div>
        <div class="w-1/6">Studentnummer</div>
        <div class="w-1/6">Opleidingsnummer</div>
        <div class="w-1/6">Cohort</div>
        <div class="w-1/6">Roostergroep</div>
        <div class="w-1/6">Acties</div>
    </div>

    <div class="divide-y divide-gray-200">
        @foreach($students as $student)
        <div class="student-row">

            {{-- Student row --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center px-4 py-3 hover:bg-gray-50 transition">
                <div class="w-full sm:w-1/4 font-semibold text-gray-800">{{ $student->user->name ?? 'Naam onbekend' }}</div>
                <div class="w-full sm:w-1/6 text-gray-700">{{ $student->studentnummer }}</div>
                <div class="w-full sm:w-1/6 text-gray-700">{{ $student->opleidingsnummer }}</div>
                <div class="w-full sm:w-1/6 text-gray-700">{{ $student->cohort_year }}</div>
                <div class="w-full sm:w-1/6 text-gray-700">{{ $student->roostergroep }}</div>

                <div class="w-full sm:w-1/6 mt-2 sm:mt-0">
                    <button class="toggle-keuzedelen bg-blue-600 hover:bg-blue-700 text-white px-4 py-1 rounded font-semibold transition w-full">
                        Toon keuzedelen
                    </button>
                </div>
            </div>

            {{-- Keuzedelen container (hidden initially) --}}
            <div class="keuzedelen-container overflow-hidden max-h-0 transition-all duration-500 ease-in-out bg-gray-50 border-l-4 border-blue-200">
                {{-- Header row for keuzedelen --}}
                <div class="flex font-semibold text-gray-700 px-4 py-2 bg-gray-100 border-b">
                    <div class="w-1/4">Titel</div>
                    <div class="w-1/6">Keuzedeel ID</div>
                    <div class="w-1/6">Prioriteit</div>
                    <div class="w-1/6">Status</div>
                    <div class="w-1/6">Acties</div>
                </div>

                {{-- Static keuzedelen --}}
                @for ($i = 1; $i <= 3; $i++)
                <div class="flex px-4 py-2 border-b last:border-b-0 items-center">
                    <div class="w-1/4 text-gray-700">Keuzedeel {{ $i }}</div>
                    <div class="w-1/6 text-gray-700">{{ 1000 + $i }}</div>
                    <div class="w-1/6 text-gray-700">{{ $i }}</div>
                    <div class="w-1/6 text-green-600 font-semibold">Bevestigd</div>
                    <div class="w-1/6">
                        <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded font-semibold">
                            Wijzig status
                        </button>
                    </div>
                    <div class="w-1/6">
                        <a href="#" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded font-semibold text-center block">
                            Bezoek keuzedeel
                        </a>
                    </div>
                </div>
                @endfor
            </div>

        </div>
        @endforeach
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.toggle-keuzedelen');
    let openContainer = null; // track currently open container

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            const studentRow = button.closest('.student-row');
            const container = studentRow.querySelector('.keuzedelen-container');

            // Close previous if it's not the same
            if (openContainer && openContainer !== container) {
                openContainer.style.maxHeight = '0';
            }

            // Toggle current
            if (container.style.maxHeight && container.style.maxHeight !== '0px') {
                container.style.maxHeight = '0';
                openContainer = null;
            } else {
                container.style.maxHeight = container.scrollHeight + 'px';
                openContainer = container;
            }
        });
    });
});
</script>

@endsection
