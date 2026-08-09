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
            $table->unsignedInteger('row_number')->default(1)->after('item_order');
            $table->index(['document_execution_section_id', 'row_number', 'item_order'], 'execution_items_row_order_index');
        });
    }

    public function down(): void
    {
        Schema::table('document_execution_items', function (Blueprint $table): void {
            $table->dropIndex('execution_items_row_order_index');
            $table->dropColumn('row_number');
        });
    }
};
