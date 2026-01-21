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
        Schema::create('inschrijvings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('keuzedeel_id');
            $table->enum('status', ['ingediend', 'goedgekeurd', 'afgewezen', 'afgerond'])->default('goedgekeurd');
            $table->text('opmerkingen')->nullable();
            $table->timestamp('inschrijfdatum')->useCurrent();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');

            $table->foreign('keuzedeel_id')
                ->references('id')
                ->on('keuzedelen')
                ->onDelete('cascade');

            $table->unique(['student_id', 'keuzedeel_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inschrijvings');
    }
};
