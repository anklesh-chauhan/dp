<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_executions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_issuance_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('execution_number')->unique();
            $table->string('document_number');
            $table->unsignedInteger('document_version');
            $table->string('document_type_code', 30);
            $table->json('workflow_configuration');
            $table->string('status', 30)->default('issued');
            $table->string('batch_number')->nullable();
            $table->string('product_name')->nullable();
            $table->string('log_frequency', 20)->nullable();
            $table->date('log_period_start')->nullable();
            $table->date('log_period_end')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('qa_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('qa_approved_at')->nullable();
            $table->text('qa_notes')->nullable();
            $table->string('disposition', 20)->default('not_applicable');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'document_type_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_executions');
    }
};
