<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sop_audit_logs', function (Blueprint $table): void {
            $table->foreignId('document_template_id')
                ->nullable()
                ->after('document_id')
                ->constrained('document_templates')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->index(['document_template_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::table('sop_audit_logs', function (Blueprint $table): void {
            $table->dropForeign(['document_template_id']);
            $table->dropIndex(['document_template_id', 'action']);
            $table->dropColumn('document_template_id');
        });
    }
};
