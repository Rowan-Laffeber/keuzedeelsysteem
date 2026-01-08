<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('inschrijvingen', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('student_id');

        $table->uuid('eerste_keuze_keuzedeel_id');
        $table->uuid('tweede_keuze_keuzedeel_id')->nullable();

        $table->uuid('toegewezen_keuzedeel_id')->nullable();

        $table->boolean('afgerond')->default(false);

        $table->enum('status', [
            'ingediend',
            'goedgekeurd',
            'afgewezen',
        ])->default('ingediend');

        $table->timestamps();

        $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        $table->foreign('eerste_keuze_keuzedeel_id')->references('id')->on('keuzedelen');
        $table->foreign('tweede_keuze_keuzedeel_id')->references('id')->on('keuzedelen');
        $table->foreign('toegewezen_keuzedeel_id')->references('id')->on('keuzedelen');
    });
}
 

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inschrijvingen');
    }
};
