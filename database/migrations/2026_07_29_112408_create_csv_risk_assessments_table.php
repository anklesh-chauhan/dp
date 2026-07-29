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
        Schema::create('csv_risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csv_validation_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('csv_requirement_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('risk_identifier');
            $table->text('hazard');
            $table->text('potential_impact');
            $table->text('existing_controls')->nullable();
            $table->unsignedTinyInteger('initial_severity');
            $table->unsignedTinyInteger('initial_probability');
            $table->unsignedTinyInteger('initial_detectability');
            $table->text('mitigation')->nullable();
            $table->unsignedTinyInteger('residual_severity')->nullable();
            $table->unsignedTinyInteger('residual_probability')->nullable();
            $table->unsignedTinyInteger('residual_detectability')->nullable();
            $table->text('acceptance_rationale')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['csv_validation_project_id', 'risk_identifier'],
                'csv_risk_project_identifier_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_risk_assessments');
    }
};
