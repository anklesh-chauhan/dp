<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_curriculum_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('curriculum_id')
                ->constrained('competency_curricula')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('controlled_document_id')
                ->constrained('controlled_documents')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['curriculum_id', 'controlled_document_id'], 'competency_curriculum_document_unique');
            $table->index(['curriculum_id', 'is_required']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_curriculum_items');
    }
};
