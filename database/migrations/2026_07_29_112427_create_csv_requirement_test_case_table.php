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
        Schema::create('csv_requirement_test_case', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csv_requirement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('csv_test_case_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['csv_requirement_id', 'csv_test_case_id'],
                'csv_requirement_test_case_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_requirement_test_case');
    }
};
