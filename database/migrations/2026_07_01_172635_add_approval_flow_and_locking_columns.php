<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('department_id')
                ->nullable()
                ->after('email')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('sop_workflows', function (Blueprint $table): void {
            $table->foreignId('department_id')
                ->nullable()
                ->after('description')
                ->constrained()
                ->nullOnDelete();

            $table->index(['department_id', 'is_active']);
        });

        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->foreignId('locked_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('locked_at')->nullable()->after('locked_by');
        });

        Schema::table('document_templates', function (Blueprint $table): void {
            $table->foreignId('locked_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('locked_at')->nullable()->after('locked_by');
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn('locked_at');
        });

        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('locked_by');
            $table->dropColumn('locked_at');
        });

        Schema::table('sop_workflows', function (Blueprint $table): void {
            $table->dropIndex(['department_id', 'is_active']);
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
