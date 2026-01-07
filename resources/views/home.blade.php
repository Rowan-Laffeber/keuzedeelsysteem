<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Keuzedeel Systeem - Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .tile-hover:hover {
            transform: scale(1.03);
            transition: transform 0.3s ease-in-out;
            cursor: pointer;
            z-index: 10;
            position: relative;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex-shrink-0">
                <img class="h-10 w-10" src="{{ asset('images/placeholder.png') }}" alt="Logo">
            </div>
            <div class="hidden md:flex space-x-6">
                <a href="{{ route('home') }}" class="text-gray-700 hover:text-blue-600 font-medium">Home</a>
                <a href="#" class="text-gray-700 hover:text-blue-600 font-medium">Overzicht</a>
                <a href="#" class="text-gray-700 hover:text-blue-600 font-medium">Contact</a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="#" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Login</a>
            </div>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto p-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">

        @foreach($parents as $parent)
            {{-- Make the whole tile clickable --}}
            <a href="{{ route('keuzedeel.info', $parent->id) }}" class="tile-hover block rounded shadow w-full bg-blue-200 p-2 flex flex-col items-center no-underline">

                {{-- Status badge --}}
                <div class="px-3 py-1 rounded-full mb-2 text-sm font-medium text-blue-900">
                    {{ $parent->status_text ?? $parent->title }}
                </div>

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

</body>
</html>
