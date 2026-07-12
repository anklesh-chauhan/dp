<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createLookupTables();

        Schema::table('document_types', function (Blueprint $table): void {
            $table->boolean('requires_sop_reference')
                ->default(false)
                ->after('code');

            $table->boolean('is_issuable')
                ->default(false)
                ->after('requires_sop_reference');
        });

        Schema::table('sop_templates', function (Blueprint $table): void {
            $table->dropIndex(['department_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropColumn('status');

            $table->foreignId('template_status_id')
                ->nullable()
                ->after('document_type_id')
                ->constrained('template_statuses')
                ->nullOnDelete();
        });

        Schema::table('sop_template_versions', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
            $table->foreignId('template_status_id')
                ->nullable()
                ->after('change_reason')
                ->constrained('template_statuses')
                ->nullOnDelete();
        });

        Schema::table('sop_template_variables', function (Blueprint $table): void {
            $table->dropColumn('datatype');
            $table->foreignId('variable_data_type_id')
                ->nullable()
                ->after('label')
                ->constrained('variable_data_types')
                ->nullOnDelete();
        });

        Schema::table('sop_documents', function (Blueprint $table): void {
            $table->dropIndex(['department_id', 'status']);
            $table->dropIndex(['document_type_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropColumn('status');

            $table->foreignId('document_status_id')
                ->after('purpose')
                ->constrained('document_statuses')
                ->restrictOnDelete();

            $table->index(['department_id', 'document_status_id']);
            $table->index(['document_type_id', 'document_status_id']);
        });

        Schema::table('sop_workflow_steps', function (Blueprint $table): void {
            $table->dropColumn('approval_type');

            $table->foreignId('approval_step_type_id')
                ->nullable()
                ->after('role_id')
                ->constrained('approval_step_types')
                ->nullOnDelete();
        });

        Schema::table('sop_approvals', function (Blueprint $table): void {
            $table->dropIndex(['decision']);
            $table->dropColumn('decision');

            $table->foreignId('approval_decision_id')
                ->nullable()
                ->after('approved_by')
                ->constrained('approval_decisions')
                ->nullOnDelete();
        });

        Schema::table('document_issuances', function (Blueprint $table): void {
            $table->dropIndex(['document_id', 'status']);
            $table->dropIndex(['status']);
            $table->dropColumn('status');

            $table->foreignId('issuance_status_id')
                ->after('issued_at')
                ->constrained('issuance_statuses')
                ->restrictOnDelete();

            $table->index(['document_id', 'issuance_status_id']);
        });

    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table): void {
            $table->dropColumn('requires_sop_reference');
            $table->dropColumn('is_issuable');
        });

        Schema::table('sop_templates', function (Blueprint $table): void {
            $table->dropColumn('template_status_id');
        });

        Schema::table('sop_template_versions', function (Blueprint $table): void {
            $table->dropColumn('template_status_id');
        });

        Schema::table('sop_template_variables', function (Blueprint $table): void {
            $table->dropColumn('variable_data_type_id');
        });

        Schema::table('sop_documents', function (Blueprint $table): void {
            $table->dropColumn('document_status_id');
        });

        Schema::table('sop_workflow_steps', function (Blueprint $table): void {
            $table->dropColumn('approval_step_type_id');
        });

        Schema::table('sop_approvals', function (Blueprint $table): void {
            $table->dropColumn('approval_decision_id');
        });

        Schema::table('document_issuances', function (Blueprint $table): void {
            $table->dropColumn('issuance_status_id');
        });

        Schema::dropIfExists('sop_roles');
        Schema::dropIfExists('approval_step_types');
        Schema::dropIfExists('issuance_statuses');
        Schema::dropIfExists('approval_decisions');
        Schema::dropIfExists('variable_data_types');
        Schema::dropIfExists('template_statuses');
        Schema::dropIfExists('document_statuses');

    }

    private function createLookupTables(): void
    {
        $createLookup = function (string $table): void {
            Schema::create($table, function (Blueprint $blueprint): void {
                $blueprint->id();
                $blueprint->string('code', 50)->unique();
                $blueprint->string('name');
                $blueprint->unsignedSmallInteger('sort_order')->default(0);
                $blueprint->timestamps();
            });
        };

        $createLookup('document_statuses');
        $createLookup('template_statuses');
        $createLookup('variable_data_types');
        $createLookup('approval_decisions');
        $createLookup('issuance_statuses');
        $createLookup('approval_step_types');
        $createLookup('sop_roles');
    }
};
