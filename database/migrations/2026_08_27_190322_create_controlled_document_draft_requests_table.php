<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_document_draft_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('controlled_document_draft_session_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('requested_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('message');
            $table->string('status')->index();
            $table->unsignedInteger('initial_preview_revision')->nullable();
            $table->string('preview_hash', 64)->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('queued_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['controlled_document_draft_session_id', 'created_at'],
                'draft_requests_session_created_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_document_draft_requests');
    }
};
