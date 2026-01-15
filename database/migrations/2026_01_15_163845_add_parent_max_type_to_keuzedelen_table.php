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
        Schema::table('keuzedelen', function (Blueprint $table) {
            $table->string('parent_max_type')->nullable()->after('maximum_studenten');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('keuzedelen', function (Blueprint $table) {
            $table->dropColumn('parent_max_type');
        });
    }
};

