<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->foreignId('category_id')
                ->nullable()
                ->after('department_id')
                ->constrained('document_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        DB::table('controlled_documents')
            ->join('document_types', 'controlled_documents.document_type_id', '=', 'document_types.id')
            ->whereNull('controlled_documents.category_id')
            ->update([
                'controlled_documents.category_id' => DB::raw('document_types.category_id'),
            ]);

        Schema::table('document_types', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table): void {
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('document_categories');
        });

        DB::table('document_types')
            ->join('document_templates', 'document_templates.document_type_id', '=', 'document_types.id')
            ->whereNull('document_types.category_id')
            ->update([
                'document_types.category_id' => DB::raw('document_templates.category_id'),
            ]);

        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
