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
            $table->string('value_type', 30)->default('text')->after('label');
            $table->string('unit', 30)->nullable()->after('value_type');
            $table->unsignedTinyInteger('decimal_precision')->nullable()->after('unit');
            $table->string('acceptance_operator', 20)->nullable()->after('decimal_precision');
            $table->decimal('acceptance_min', 20, 8)->nullable()->after('acceptance_operator');
            $table->decimal('acceptance_max', 20, 8)->nullable()->after('acceptance_min');
        });
    }

    public function down(): void
    {
        Schema::table('controlled_document_section_items', function (Blueprint $table): void {
            $table->dropColumn([
                'value_type',
                'unit',
                'decimal_precision',
                'acceptance_operator',
                'acceptance_min',
                'acceptance_max',
            ]);
        });
    }
};
