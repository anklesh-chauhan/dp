<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('batch_uuid')->unique();
            $table->string('name')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('source_type')->default('single');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('successful_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('document_import_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('document_import_batches')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('status')->default('pending')->index();
            $table->string('mode')->default('archive');
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('controlled_document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['batch_id', 'status']);
        });

        Schema::create('document_original_artifacts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('artifact_uuid')->unique();
            $table->foreignId('import_item_id')->constrained('document_import_items')->cascadeOnDelete();
            $table->foreignId('controlled_document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['import_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_original_artifacts');
        Schema::dropIfExists('document_import_items');
        Schema::dropIfExists('document_import_batches');
    }
};
