<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex-shrink-0">
                <img class="h-10 w-10" src="{{ asset('images/placeholder.png') }}" alt="Logo">
            </div>
            <div class="hidden md:flex space-x-6">
                <a href="{{ route('home') }}" class="text-gray-700 hover:text-blue-600 font-medium">Home</a>
                @if(auth()->user()->role === 'student')
                <a href="{{ route('profile') }}" class="text-gray-700 hover:text-blue-600 font-medium">Mijn Keuzedelen</a>
                <a href="{{ route('more-options.index') }}" class="text-gray-700 hover:text-blue-600 font-medium">Keuzes Opgeven</a>
                @endif
                @if(auth()->user()->role === 'admin')
                <a href="{{ route('create') }}" class="text-gray-700 hover:text-blue-600 font-medium">Creëer keuzedeel</a>
                <a href="{{ route('upload') }}" class="text-gray-700 hover:text-blue-600 font-medium">Upload CSV</a>
                <a href="{{ route('studentoverzicht') }}" class="text-gray-700 hover:text-blue-600 font-medium">Ingeschreven Studenten</a>
                @endif
                <a href="#" class="text-gray-700 hover:text-blue-600 font-medium">Contact</a>
            </div>
            <div class="flex items-center space-x-4">
                @auth
                    <span class="text-gray-700 font-medium">
                        {{ auth()->user()->name }} 
                        ({{ auth()->user()->role }})
                        @if(auth()->user()->role === 'student' && auth()->user()->student)
                            ({{ auth()->user()->student->opleidingsnummer }})
                        @endif
                </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Login</a>
                @endauth
            </div>
        </div>
    </div>
</header>
