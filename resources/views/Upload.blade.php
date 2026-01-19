@extends('layouts.app')

@section('title', 'Keuzedeel Systeem - Upload CSV')

@section('content')
<main class="max-w-4xl mx-auto p-6">

    <div class="bg-white shadow rounded p-6">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Upload CSV</h1>

        {{-- Success message --}}
        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4 text-center font-medium shadow">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error message --}}
        @if (session('error'))
            <div class="bg-red-100 text-red-800 p-3 rounded mb-4 text-center font-medium shadow">
                 {{ session('error') }}
            </div>
        @endif

        {{-- Validation errors --}}
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 p-3 rounded mb-4 shadow">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('upload') }}" enctype="multipart/form-data" class="flex flex-col items-center space-y-4">
            @csrf
            <input type="file" name="csv_file" accept=".csv" class="border border-gray-300 rounded p-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition-colors w-full font-medium">
                Upload CSV
            </button>
        </form>
    </div>

</main>
@endsection
