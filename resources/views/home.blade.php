@extends('layouts.app')

@section('title', 'Keuzedeel Systeem - Home')

@section('content')

<main class="max-w-7xl mx-auto p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">

        @foreach($parents as $parent)
            {{-- Make the whole tile clickable --}}
            <a href="{{ route('keuzedeel.info', $parent->id) }}" 
               class="tile-hover block rounded shadow w-full bg-blue-200 p-2 flex flex-col items-center no-underline">

                {{-- Enrollment status badge --}}
                @if(isset($parent->is_ingeschreven) && $parent->is_ingeschreven)
                    <div class="px-3 py-1 rounded-full mb-2 text-sm font-medium 
                        @if($parent->enrollment_status === 'goedgekeurd') bg-green-100 text-green-800
                        @elseif($parent->enrollment_status === 'ingediend') bg-yellow-100 text-yellow-800
                        @else bg-gray-100 text-gray-800 @endif">
                        Prio {{ $parent->enrollment_priority }} · {{ ucfirst($parent->enrollment_status) }}
                        @if($parent->enrollment_keuzedeel)
                            ({{ $parent->enrollment_keuzedeel->title }})
                        @endif
                    </div>
                @else
                    {{-- Status badge for non-enrolled --}}
                    <div class="px-3 py-1 rounded-full mb-2 text-sm font-medium text-blue-900">
                        {{ $parent->status_text ?? $parent->title }}
                    </div>
                @endif

                {{-- Tile content --}}
                <div class="bg-white p-4 rounded w-full flex flex-col items-center">
                    <h2 class="text-lg font-bold mb-2">{{ $parent->title }}</h2>
                    <p class="text-gray-600 mb-2 text-center">{{ $parent->description }}</p>
                    <img src="{{ asset('images/placeholder.png') }}" alt="{{ $parent->title }}" class="w-full h-32 object-cover rounded">
                </div>

            </a>
        @endforeach

    </div>
</main>

@endsection

@push('styles')
<style>
.tile-hover:hover {
    transform: scale(1.03);
    transition: transform 0.3s ease-in-out;
    cursor: pointer;
    z-index: 10;
    position: relative;
}
</style>
@endpush
