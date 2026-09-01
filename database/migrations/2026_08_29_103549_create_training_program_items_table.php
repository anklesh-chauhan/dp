<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_program_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('training_program_id')
                ->constrained('training_programs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('controlled_document_id')
                ->constrained('controlled_documents')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(
                ['training_program_id', 'controlled_document_id'],
                'training_program_document_unique',
            );
            $table->index(['training_program_id', 'is_required']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_program_items');
    }
};
