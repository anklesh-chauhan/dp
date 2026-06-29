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
            $table->foreignId('sop_template_id')
                ->nullable()
                ->after('document_id')
                ->constrained('sop_templates')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->index(['sop_template_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::table('sop_audit_logs', function (Blueprint $table): void {
            $table->dropForeign(['sop_template_id']);
            $table->dropIndex(['sop_template_id', 'action']);
            $table->dropColumn('sop_template_id');
        });
    }
};
