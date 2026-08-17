<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_m_gap_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('schedule_m_gap_assessments')->cascadeOnDelete();
            $table->string('part_code')->default('Part_I')->index();
            $table->string('clause_ref')->index();
            $table->string('clause_title');
            $table->string('status')->default(ScheduleMGapItemStatus::NotAssessed->value)->index();
            $table->longText('evidence_notes')->nullable();
            $table->foreignId('evidence_document_id')->nullable()->constrained('controlled_documents')->nullOnDelete();
            $table->nullableMorphs('evidence_qms');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'part_code', 'clause_ref'], 'schedule_m_gap_item_clause_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_m_gap_items');
    }
};
