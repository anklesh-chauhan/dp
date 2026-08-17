<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_m_gap_assessments', function (Blueprint $table) {
            $table->id();
            $table->string('assessment_number')->unique();
            $table->string('title');
            $table->string('status')->default(ScheduleMGapAssessmentStatus::Draft->value)->index();
            $table->string('site_name')->index();
            $table->string('period_label')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_m_gap_assessments');
    }
};
