<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 40)->index();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('controlled_document_id')
                ->nullable()
                ->constrained('controlled_documents')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('training_program_id')
                ->nullable()
                ->constrained('training_programs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->text('completion_comments')->nullable();
            $table->timestamps();

            $table->unique(['controlled_document_id', 'user_id']);
            $table->unique(['training_program_id', 'user_id']);
            $table->index(['user_id', 'completed_at']);
            $table->index(['source_type', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assignments');
    }
};
