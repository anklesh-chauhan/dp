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
        Schema::create('regulation_tag_document_template', function (Blueprint $table): void {
            $table->foreignId('regulation_tag_id')->constrained('regulation_tags')->cascadeOnDelete();
            $table->foreignId('document_template_id')->constrained('document_templates')->cascadeOnDelete();
            $table->primary(['regulation_tag_id', 'document_template_id']);
        });

        Schema::create('regulation_tag_document', function (Blueprint $table): void {
            $table->foreignId('regulation_tag_id')->constrained('regulation_tags')->cascadeOnDelete();
            $table->foreignId('controlled_document_id')->constrained('controlled_documents')->cascadeOnDelete();
            $table->primary(['regulation_tag_id', 'controlled_document_id']);
        });

        $this->backfillTemplateRegulationTags();
        $this->backfillDocumentRegulationTags();
    }

    public function down(): void
    {
        Schema::dropIfExists('regulation_tag_document');
        Schema::dropIfExists('regulation_tag_document_template');
    }

    private function backfillTemplateRegulationTags(): void
    {
        $rows = DB::table('regulation_tag_document_type')
            ->join('document_templates', 'document_templates.document_type_id', '=', 'regulation_tag_document_type.document_type_id')
            ->select('regulation_tag_document_type.regulation_tag_id', 'document_templates.id as document_template_id')
            ->distinct()
            ->get();

        foreach ($rows as $row) {
            DB::table('regulation_tag_document_template')->insertOrIgnore([
                'regulation_tag_id' => $row->regulation_tag_id,
                'document_template_id' => $row->document_template_id,
            ]);
        }
    }

    private function backfillDocumentRegulationTags(): void
    {
        $rows = DB::table('regulation_tag_document_type')
            ->join('controlled_documents', 'controlled_documents.document_type_id', '=', 'regulation_tag_document_type.document_type_id')
            ->select('regulation_tag_document_type.regulation_tag_id', 'controlled_documents.id as controlled_document_id')
            ->distinct()
            ->get();

        foreach ($rows as $row) {
            DB::table('regulation_tag_document')->insertOrIgnore([
                'regulation_tag_id' => $row->regulation_tag_id,
                'controlled_document_id' => $row->controlled_document_id,
            ]);
        }
    }
};
