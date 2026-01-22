<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keuzedelen', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('title');
            $table->text('description')->nullable();

            $table->boolean('actief')->default(true);

            $table->integer('minimum_studenten')->default(15);
            $table->integer('maximum_studenten')->nullable();

            $table->enum('parent_max_type', ['parent','subdeel'])->default('subdeel');

            $table->dateTime('start_inschrijving');
            $table->dateTime('eind_inschrijving');

            $table->uuid('parent_id')->nullable();
            $table->integer('volgorde')->nullable();

            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('keuzedelen')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keuzedelen');
    }
};
