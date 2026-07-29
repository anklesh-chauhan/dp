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
        Schema::create('csv_test_executions', function (Blueprint $table) {
            $table->id();
            $table->uuid('execution_uuid')->unique();
            $table->foreignId('csv_validation_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('csv_test_case_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('execution_no');
            $table->string('environment');
            $table->string('application_version');
            $table->string('commit_sha')->nullable();
            $table->string('configuration_hash', 64)->nullable();
            $table->json('step_results');
            $table->string('result')->index();
            $table->text('actual_result')->nullable();
            $table->text('evidence_summary')->nullable();
            $table->foreignId('deviation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['csv_test_case_id', 'execution_no'],
                'csv_test_execution_case_number_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_test_executions');
    }
};
