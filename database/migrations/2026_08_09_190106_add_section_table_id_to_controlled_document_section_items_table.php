<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controlled_document_section_items', function (Blueprint $table): void {
            $table->foreignId('section_table_id')
                ->nullable()
                ->after('section_id')
                ->constrained('controlled_document_section_tables')
                ->cascadeOnDelete();
            $table->index(['section_table_id', 'item_order']);
        });
    }

    public function down(): void
    {
        Schema::table('controlled_document_section_items', function (Blueprint $table): void {
            $table->dropIndex(['section_table_id', 'item_order']);
            $table->dropConstrainedForeignId('section_table_id');
        });
    }
};
