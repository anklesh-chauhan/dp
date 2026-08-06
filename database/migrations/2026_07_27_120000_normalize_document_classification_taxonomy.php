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

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('UPDATE controlled_documents AS documents SET category_id = types.category_id FROM document_types AS types WHERE documents.document_type_id = types.id AND documents.category_id IS NULL');
        } else {
            DB::table('controlled_documents')
                ->join('document_types', 'controlled_documents.document_type_id', '=', 'document_types.id')
                ->whereNull('controlled_documents.category_id')
                ->update(['controlled_documents.category_id' => DB::raw('document_types.category_id')]);
        }

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

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('UPDATE document_types AS types SET category_id = templates.category_id FROM document_templates AS templates WHERE templates.document_type_id = types.id AND types.category_id IS NULL');
        } else {
            DB::table('document_types')
                ->join('document_templates', 'document_templates.document_type_id', '=', 'document_types.id')
                ->whereNull('document_types.category_id')
                ->update(['document_types.category_id' => DB::raw('document_templates.category_id')]);
        }

        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
