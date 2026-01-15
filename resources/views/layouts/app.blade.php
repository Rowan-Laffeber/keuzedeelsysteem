<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Keuzedeel App')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

@include('partials.header') {{-- Reusable header --}}

<main class="max-w-7xl mx-auto p-6">
    @yield('content') {{-- Page-specific content goes here --}}
</main>

@yield('scripts') {{-- Page-specific scripts go here --}}

</body>
</html>
