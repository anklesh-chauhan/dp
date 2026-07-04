<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->unique();
            $table->timestamps();
        });

        Schema::create('document_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->unique();
            $table->timestamps();
        });

        Schema::create('document_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 20)->unique();
            $table->timestamps();
        });

        Schema::create('sop_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->foreignId('department_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('category_id')->constrained('document_categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('document_type_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status')->default('draft')->index();
            $table->unsignedInteger('current_version')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['department_id', 'status']);
        });

        Schema::create('sop_template_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sop_template_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('content_json')->nullable();
            $table->date('effective_date')->nullable();
            $table->text('change_reason')->nullable();
            $table->string('status')->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['sop_template_id', 'version']);
        });

        Schema::create('sop_template_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_version_id')->constrained('sop_template_versions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('section_order')->default(1);
            $table->string('section_type')->default('rich_text');
            $table->longText('content')->nullable();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            $table->index(['template_version_id', 'section_order']);
        });

        Schema::create('sop_template_variables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_version_id')->constrained('sop_template_versions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name');
            $table->string('label');
            $table->string('datatype')->default('text');
            $table->text('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->boolean('required')->default(false);
            $table->timestamps();
            $table->unique(['template_version_id', 'name']);
        });

        Schema::create('sop_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('template_id')->constrained('sop_templates')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('template_version_id')->constrained('sop_template_versions')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('document_number')->unique();
            $table->string('title');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('department_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('status')->default('draft')->index();
            $table->date('effective_date')->nullable();
            $table->date('review_date')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['department_id', 'status']);
        });

        Schema::create('sop_document_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('sop_documents')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('section_order')->default(1);
            $table->longText('content')->nullable();
            $table->timestamps();
            $table->index(['document_id', 'section_order']);
        });

        Schema::create('sop_document_variables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('sop_documents')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('variable_name');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'variable_name']);
        });

        Schema::create('sop_workflows', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('sop_workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('sop_workflows')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedInteger('step_no');
            $table->foreignId('role_id')->constrained('roles')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('approval_type')->default('approval');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
            $table->unique(['workflow_id', 'step_no']);
        });

        Schema::create('sop_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->constrained('sop_documents')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->constrained('sop_workflow_steps')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision')->default('pending')->index();
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('signature_hash')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'workflow_step_id']);
        });

        Schema::create('sop_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained('sop_documents')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['document_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sop_audit_logs');
        Schema::dropIfExists('sop_approvals');
        Schema::dropIfExists('sop_workflow_steps');
        Schema::dropIfExists('sop_workflows');
        Schema::dropIfExists('sop_document_variables');
        Schema::dropIfExists('sop_document_sections');
        Schema::dropIfExists('sop_documents');
        Schema::dropIfExists('sop_template_variables');
        Schema::dropIfExists('sop_template_sections');
        Schema::dropIfExists('sop_template_versions');
        Schema::dropIfExists('sop_templates');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('document_categories');
        Schema::dropIfExists('departments');
    }
};
