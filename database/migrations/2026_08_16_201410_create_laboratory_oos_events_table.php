<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\LaboratoryOosPhaseOutcome;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Enums\LaboratoryOosType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_oos_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_number')->unique();
            $table->string('type')->default(LaboratoryOosType::Oos->value)->index();
            $table->string('status')->default(LaboratoryOosStatus::Draft->value)->index();
            $table->string('title');
            $table->string('test_name');
            $table->string('method_reference')->nullable();
            $table->string('sample_id')->nullable()->index();
            $table->string('batch_number')->nullable()->index();
            $table->string('product_name')->nullable()->index();
            $table->string('specification_limit')->nullable();
            $table->string('observed_result')->nullable();
            $table->string('unit')->nullable();
            $table->string('phase_one_outcome')->default(LaboratoryOosPhaseOutcome::Pending->value)->nullable();
            $table->text('phase_one_notes')->nullable();
            $table->string('phase_two_outcome')->nullable();
            $table->text('phase_two_notes')->nullable();
            $table->text('hypothesis')->nullable();
            $table->text('invalidation_justification')->nullable();
            $table->foreignId('investigation_id')->nullable()->constrained('investigations')->nullOnDelete();
            $table->foreignId('deviation_id')->nullable()->constrained('deviations')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('analyst_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('phase_one_completed_at')->nullable();
            $table->timestamp('phase_two_completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_oos_events');
    }
};
