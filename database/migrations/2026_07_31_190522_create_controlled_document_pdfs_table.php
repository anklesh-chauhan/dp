<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('controlled_document_pdfs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('controlled_document_id')->constrained()->restrictOnDelete();
            $table->foreignId('report_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_issuance_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('document_version');
            $table->string('template_layout_key');
            $table->string('disk')->default('local');
            $table->string('path')->unique();
            $table->string('filename');
            $table->string('mime_type')->default('application/pdf');
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64)->index();
            $table->string('renderer');
            $table->string('renderer_version')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(
                ['controlled_document_id', 'document_version', 'report_template_id', 'document_issuance_id'],
                'controlled_document_pdf_lookup',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controlled_document_pdfs');
    }
};
