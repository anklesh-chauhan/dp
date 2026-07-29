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
        Schema::create('csv_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csv_validation_project_id')->constrained()->cascadeOnDelete();
            $table->string('requirement_identifier');
            $table->unsignedInteger('version')->default(1);
            $table->string('category')->index();
            $table->text('statement');
            $table->text('rationale')->nullable();
            $table->text('source_reference')->nullable();
            $table->text('acceptance_criteria');
            $table->string('criticality')->index();
            $table->boolean('gxp_relevant')->default(true);
            $table->boolean('data_integrity_relevant')->default(false);
            $table->string('status')->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['csv_validation_project_id', 'requirement_identifier', 'version'],
                'csv_requirement_project_identifier_version_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_requirements');
    }
};
