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
        Schema::create('csv_validation_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('project_uuid')->unique();
            $table->string('project_number')->unique();
            $table->string('system_identifier');
            $table->string('system_name');
            $table->string('system_version')->nullable();
            $table->text('intended_use');
            $table->string('status')->index();
            $table->string('gxp_criticality')->index();
            $table->boolean('is_gxp')->default(true);
            $table->boolean('uses_electronic_records')->default(true);
            $table->boolean('uses_electronic_signatures')->default(true);
            $table->json('regulatory_scope')->nullable();
            $table->text('validation_strategy')->nullable();
            $table->json('release_baseline')->nullable();
            $table->text('validation_summary')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('system_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('quality_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validation_plan_document_id')->nullable()->constrained('controlled_documents')->nullOnDelete();
            $table->foreignId('summary_report_document_id')->nullable()->constrained('controlled_documents')->nullOnDelete();
            $table->foreignId('change_control_id')->nullable()->constrained()->nullOnDelete();
            $table->date('planned_release_date')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->date('next_periodic_review_date')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();

            $table->index(['system_identifier', 'system_version']);
            $table->index(['status', 'next_periodic_review_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_validation_projects');
    }
};
