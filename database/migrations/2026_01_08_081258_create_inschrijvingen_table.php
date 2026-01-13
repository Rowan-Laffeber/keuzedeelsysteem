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


        $table->uuid('keuzedeel_id')->nullable();


        $table->enum('status', [
            'ingediend',
            'goedgekeurd',
            'afgewezen',
            'afgerond',
        ])->default('ingediend');

        $table->timestamps();

        $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
        $table->foreign('keuzedeel_id')->references('id')->on('keuzedelen');
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
