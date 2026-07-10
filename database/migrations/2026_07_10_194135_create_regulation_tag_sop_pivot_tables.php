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
        Schema::create('regulation_tag_sop_template', function (Blueprint $table): void {
            $table->foreignId('regulation_tag_id')->constrained('regulation_tags')->cascadeOnDelete();
            $table->foreignId('sop_template_id')->constrained('sop_templates')->cascadeOnDelete();
            $table->primary(['regulation_tag_id', 'sop_template_id']);
        });

        Schema::create('regulation_tag_sop_document', function (Blueprint $table): void {
            $table->foreignId('regulation_tag_id')->constrained('regulation_tags')->cascadeOnDelete();
            $table->foreignId('sop_document_id')->constrained('sop_documents')->cascadeOnDelete();
            $table->primary(['regulation_tag_id', 'sop_document_id']);
        });

        $this->backfillTemplateRegulationTags();
        $this->backfillDocumentRegulationTags();
    }

    public function down(): void
    {
        Schema::dropIfExists('regulation_tag_sop_document');
        Schema::dropIfExists('regulation_tag_sop_template');
    }

    private function backfillTemplateRegulationTags(): void
    {
        $rows = DB::table('regulation_tag_document_type')
            ->join('sop_templates', 'sop_templates.document_type_id', '=', 'regulation_tag_document_type.document_type_id')
            ->select('regulation_tag_document_type.regulation_tag_id', 'sop_templates.id as sop_template_id')
            ->distinct()
            ->get();

        foreach ($rows as $row) {
            DB::table('regulation_tag_sop_template')->insertOrIgnore([
                'regulation_tag_id' => $row->regulation_tag_id,
                'sop_template_id' => $row->sop_template_id,
            ]);
        }
    }

    private function backfillDocumentRegulationTags(): void
    {
        $rows = DB::table('regulation_tag_document_type')
            ->join('sop_documents', 'sop_documents.document_type_id', '=', 'regulation_tag_document_type.document_type_id')
            ->select('regulation_tag_document_type.regulation_tag_id', 'sop_documents.id as sop_document_id')
            ->distinct()
            ->get();

        foreach ($rows as $row) {
            DB::table('regulation_tag_sop_document')->insertOrIgnore([
                'regulation_tag_id' => $row->regulation_tag_id,
                'sop_document_id' => $row->sop_document_id,
            ]);
        }
    }
};
