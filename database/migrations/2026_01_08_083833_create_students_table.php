<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('studentnummer')->unique();
            $table->string('opleidingsnummer');
            $table->string('cohort_year');
            $table->string('roostergroep');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
        public function inschrijven($keuzedeelId)
{
    $student = auth()->user()->student;
    $keuzedeel = Keuzedeel::findOrFail($keuzedeelId);


    if ($student->keuzedelen()
        ->where('keuzedeel_id', $keuzedeelId)
        ->exists()) {
        return back()->with('error', 'Je bent al ingeschreven.');
    }

   
    if ($keuzedeel->students()->count() >= $keuzedeel->max_inschrijvingen) {
        return back()->with('error', 'Dit keuzedeel zit vol.');
    }

    
    $student->keuzedelen()->attach($keuzedeelId);

    return back()->with('success', 'Succesvol ingeschreven!');
}
};


