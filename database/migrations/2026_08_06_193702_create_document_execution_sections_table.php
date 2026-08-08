<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_execution_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_execution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_section_id')->nullable()->constrained('controlled_document_sections')->nullOnDelete();
            $table->string('title');
            $table->unsignedInteger('section_order')->default(1);
            $table->string('section_type', 30)->default('rich_text');
            $table->longText('content')->nullable();
            $table->json('configuration')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('completion_notes')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['document_execution_id', 'source_section_id']);
            $table->index(['document_execution_id', 'section_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_execution_sections');
    }
};
