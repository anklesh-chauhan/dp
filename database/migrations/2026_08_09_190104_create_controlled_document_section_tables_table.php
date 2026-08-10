<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_document_section_tables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained('controlled_document_sections')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->unsignedInteger('table_order')->default(1);
            $table->string('execution_layout', 30)->default('table');
            $table->unsignedInteger('row_count')->default(1);
            $table->timestamps();

            $table->index(['section_id', 'table_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_document_section_tables');
    }
};
