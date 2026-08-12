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
        Schema::create('controlled_document_draft_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('document_templates')->restrictOnDelete();
            $table->foreignId('template_version_id')->constrained('document_template_versions')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('referenced_controlled_document_id')->nullable()->constrained('controlled_documents')->nullOnDelete();
            $table->string('conversation_id', 36)->nullable()->unique();
            $table->string('status')->default('gathering')->index();
            $table->string('title')->nullable();
            $table->json('brief')->nullable();
            $table->json('draft_variables')->nullable();
            $table->unsignedInteger('preview_revision')->default(0);
            $table->string('preview_hash', 64)->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('controlled_document_id')->nullable()->constrained('controlled_documents')->nullOnDelete();
            $table->timestamps();

            $table->index(['created_by', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controlled_document_draft_sessions');
    }
};
