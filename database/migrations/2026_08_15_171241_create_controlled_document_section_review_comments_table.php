<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_document_section_review_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('controlled_documents')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('controlled_document_sections')->cascadeOnDelete();
            $table->foreignId('sop_approval_id')->nullable()->constrained('sop_approvals')->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['document_id', 'resolved_at']);
            $table->index(['section_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_document_section_review_comments');
    }
};
