<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Login - Keuzedeel Systeem</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

<header class="bg-white shadow mb-8">
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
        </div>
    </div>
</header>

<main class="max-w-md mx-auto bg-white p-8 rounded shadow">
    <h1 class="text-2xl font-bold mb-6 text-center">Inloggen</h1>

    @if(session('error'))
        <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block font-medium text-gray-700">E-mailadres</label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <div>
            <label for="password" class="block font-medium text-gray-700">Wachtwoord</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                class="mt-1 block w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
        </div>

        <div class="flex items-center justify-between">
            <div>
                <input type="checkbox" name="remember" id="remember" class="mr-1">
                <label for="remember" class="text-gray-700 text-sm">Onthoud mij</label>
            </div>
            <a href="#" class="text-blue-600 hover:underline text-sm">Wachtwoord vergeten?</a>
        </div>

        <button
            type="submit"
            class="w-full bg-blue-600 text-white font-semibold px-4 py-2 rounded hover:bg-blue-700"
        >
            Inloggen
        </button>
    </form>
</main>

</body>
</html>
