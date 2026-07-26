<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AuditFindingClassification;
use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\AuditFindingSeverity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_findings', function (Blueprint $table) {
            $table->id();
            $table->string('finding_number')->unique();
            $table->foreignId('internal_audit_id')->constrained()->restrictOnDelete();
            $table->string('severity')->default(AuditFindingSeverity::Minor->value)->index();
            $table->string('classification')->default(AuditFindingClassification::Observation->value)->index();
            $table->string('disposition')->default(AuditFindingDisposition::Open->value)->index();
            $table->string('clause_reference')->nullable()->index();
            $table->string('title');
            $table->text('description');
            $table->text('objective_evidence');
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('identified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('identified_at');
            $table->date('response_due_at')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_findings');
    }
};
