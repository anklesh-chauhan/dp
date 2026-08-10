<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_execution_items', function (Blueprint $table): void {
            $table->foreignId('source_table_id')
                ->nullable()
                ->after('source_item_id')
                ->constrained('controlled_document_section_tables')
                ->nullOnDelete();
            $table->string('table_title')->nullable()->after('source_table_id');
            $table->unsignedInteger('table_order')->default(1)->after('table_title');
            $table->string('table_layout', 30)->default('table')->after('table_order');
            $table->index(
                ['document_execution_section_id', 'table_order', 'row_number', 'item_order'],
                'execution_items_table_row_order_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('document_execution_items', function (Blueprint $table): void {
            $table->dropIndex('execution_items_table_row_order_index');
            $table->dropConstrainedForeignId('source_table_id');
            $table->dropColumn(['table_title', 'table_order', 'table_layout']);
        });
    }
};
