<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_document_training_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('controlled_documents')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->text('completion_comments')->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'user_id']);
            $table->index(['document_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_document_training_assignments');
    }
};
