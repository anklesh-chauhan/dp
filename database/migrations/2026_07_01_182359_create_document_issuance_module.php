<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sop_documents', function (Blueprint $table): void {
            $table->foreignId('document_type_id')
                ->nullable()
                ->after('department_id')
                ->constrained('document_types')
                ->nullOnDelete();

            $table->foreignId('referenced_sop_document_id')
                ->nullable()
                ->after('document_type_id')
                ->constrained('sop_documents')
                ->nullOnDelete();

            $table->string('referenced_sop_number')->nullable()->after('referenced_sop_document_id');
            $table->unsignedInteger('referenced_sop_version')->nullable()->after('referenced_sop_number');
            $table->date('referenced_sop_effective_date')->nullable()->after('referenced_sop_version');
            $table->string('batch_number')->nullable()->after('referenced_sop_effective_date');
            $table->string('product_name')->nullable()->after('batch_number');
            $table->text('purpose')->nullable()->after('product_name');

            $table->index(['document_type_id', 'status']);
            $table->index('referenced_sop_document_id');
        });

        Schema::create('document_issuances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('sop_documents')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unsignedInteger('copy_number');
            $table->string('issuance_number')->unique();
            $table->foreignId('issued_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('issued_to_department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();
            $table->string('issued_to_location')->nullable();
            $table->foreignId('issued_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('issued_at');
            $table->string('status')->default('active')->index();
            $table->foreignId('recalled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recalled_at')->nullable();
            $table->text('recall_reason')->nullable();
            $table->foreignId('destroyed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('destroyed_at')->nullable();
            $table->text('destroy_reason')->nullable();
            $table->string('watermark_code');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'copy_number']);
            $table->index(['document_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_issuances');

        Schema::table('sop_documents', function (Blueprint $table): void {
            $table->dropIndex(['document_type_id', 'status']);
            $table->dropIndex(['referenced_sop_document_id']);
            $table->dropConstrainedForeignId('referenced_sop_document_id');
            $table->dropConstrainedForeignId('document_type_id');
            $table->dropColumn([
                'referenced_sop_number',
                'referenced_sop_version',
                'referenced_sop_effective_date',
                'batch_number',
                'product_name',
                'purpose',
            ]);
        });
    }
};
