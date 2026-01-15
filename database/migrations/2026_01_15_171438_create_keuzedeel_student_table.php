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
        Schema::create('keuzedeel_student', function (Blueprint $table) {
            $table->uuid('keuzedeel_id');
            $table->uuid('student_id');
            $table->timestamps();

            $table->foreign('keuzedeel_id')
                ->references('id')
                ->on('keuzedelen')
                ->onDelete('cascade');

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');

            $table->primary(['keuzedeel_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keuzedeel_student');
    }
};
