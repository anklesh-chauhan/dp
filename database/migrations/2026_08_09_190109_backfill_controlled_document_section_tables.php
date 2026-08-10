<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('controlled_document_sections')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('controlled_document_section_items')
                    ->whereColumn('controlled_document_section_items.section_id', 'controlled_document_sections.id');
            })
            ->orderBy('id')
            ->eachById(function (object $section): void {
                $configuration = is_string($section->configuration)
                    ? json_decode($section->configuration, true)
                    : [];

                $tableId = DB::table('controlled_document_section_tables')->insertGetId([
                    'section_id' => $section->id,
                    'title' => null,
                    'table_order' => 1,
                    'execution_layout' => data_get($configuration, 'execution_layout', 'table'),
                    'row_count' => min(100, max(1, (int) data_get($configuration, 'execution_row_count', 1))),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('controlled_document_section_items')
                    ->where('section_id', $section->id)
                    ->update(['section_table_id' => $tableId]);
            });
    }

    public function down(): void
    {
        DB::table('controlled_document_section_items')->update(['section_table_id' => null]);
        DB::table('controlled_document_section_tables')->delete();
    }
};
