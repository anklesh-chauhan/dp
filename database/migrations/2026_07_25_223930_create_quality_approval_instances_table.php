<?php

declare(strict_types=1);

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
        Schema::create('quality_approval_instances', function (Blueprint $table) {
            $table->id();
            $table->uuid('instance_uuid')->unique();
            $table->uuid('submission_uuid')->index();
            $table->morphs('subject');
            $table->foreignId('workflow_id')
                ->constrained('quality_approval_workflows')
                ->restrictOnDelete();
            $table->foreignId('workflow_step_id')
                ->constrained('quality_approval_workflow_steps')
                ->restrictOnDelete();
            $table->string('decision_code')->default('pending')->index();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comments')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('signature_hash', 64)->nullable();
            $table->ipAddress('signature_ip_address')->nullable();
            $table->text('signature_user_agent')->nullable();
            $table->timestamps();

            $table->unique(
                ['subject_type', 'subject_id', 'submission_uuid', 'workflow_step_id'],
                'quality_approval_submission_step_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_approval_instances');
    }
};
