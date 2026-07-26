<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Enums\RiskAssessmentType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->string('risk_number')->unique();
            $table->string('type')->default(RiskAssessmentType::Process->value)->index();
            $table->string('status')->default(RiskAssessmentStatus::Draft->value)->index();
            $table->string('title');
            $table->text('scope');
            $table->text('hazard');
            $table->text('potential_harm');
            $table->text('existing_controls')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('initial_severity');
            $table->unsignedTinyInteger('initial_probability');
            $table->unsignedTinyInteger('initial_detectability');
            $table->text('mitigation_plan')->nullable();
            $table->date('mitigation_due_at')->nullable();
            $table->timestamp('mitigation_completed_at')->nullable();
            $table->unsignedTinyInteger('residual_severity')->nullable();
            $table->unsignedTinyInteger('residual_probability')->nullable();
            $table->unsignedTinyInteger('residual_detectability')->nullable();
            $table->date('review_due_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};
