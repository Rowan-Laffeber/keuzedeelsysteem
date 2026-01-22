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
        Schema::create('more_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('keuzedeel_id');
            $table->integer('priority'); // 1, 2, or 3
            $table->enum('status', ['pending', 'assigned', 'rejected'])->default('pending');
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');

            $table->foreign('keuzedeel_id')
                ->references('id')
                ->on('keuzedelen')
                ->onDelete('cascade');

            $table->unique(['student_id', 'priority']); // One choice per priority per student
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('more_options');
    }
};
