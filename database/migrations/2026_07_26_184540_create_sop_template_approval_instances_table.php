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
        Schema::create('sop_template_approval_instances', function (Blueprint $table) {
            $table->id();
            $table->uuid('instance_uuid')->unique();
            $table->uuid('submission_uuid')->index();
            $table->foreignId('sop_template_version_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('workflow_id')->constrained('sop_workflows')->restrictOnDelete();
            $table->foreignId('workflow_step_id')->constrained('sop_workflow_steps')->restrictOnDelete();
            $table->string('decision_code')->default('pending')->index();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comments')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('signature_hash', 64)->nullable();
            $table->ipAddress('signature_ip_address')->nullable();
            $table->text('signature_user_agent')->nullable();
            $table->timestamps();

            $table->unique(
                ['sop_template_version_id', 'submission_uuid', 'workflow_step_id'],
                'template_approval_submission_step_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sop_template_approval_instances');
    }
};
