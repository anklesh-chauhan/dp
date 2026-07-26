<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Enums\InternalAuditType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_number')->unique();
            $table->string('type')->default(InternalAuditType::Internal->value)->index();
            $table->string('status')->default(InternalAuditStatus::Draft->value)->index();
            $table->string('title');
            $table->text('scope');
            $table->text('objectives')->nullable();
            $table->text('criteria')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('lead_auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('scheduled_start_at')->nullable()->index();
            $table->date('scheduled_end_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('report_issued_at')->nullable();
            $table->date('follow_up_due_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_audits');
    }
};
