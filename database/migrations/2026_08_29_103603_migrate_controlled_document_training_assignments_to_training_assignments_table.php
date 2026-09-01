<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('controlled_document_training_assignments')) {
            return;
        }

        DB::table('controlled_document_training_assignments')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                $payload = [];

                foreach ($rows as $row) {
                    $payload[] = [
                        'source_type' => 'controlled_document',
                        'user_id' => $row->user_id,
                        'controlled_document_id' => $row->document_id,
                        'training_program_id' => null,
                        'assigned_by' => $row->assigned_by,
                        'assigned_at' => $row->assigned_at,
                        'completed_at' => $row->completed_at,
                        'completion_comments' => $row->completion_comments,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if ($payload !== []) {
                    DB::table('training_assignments')->insert($payload);
                }
            });

        Schema::dropIfExists('controlled_document_training_assignments');
    }

    public function down(): void
    {
        if (Schema::hasTable('controlled_document_training_assignments')) {
            return;
        }

        Schema::create('controlled_document_training_assignments', function ($table): void {
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

        DB::table('training_assignments')
            ->where('source_type', 'controlled_document')
            ->whereNotNull('controlled_document_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                $payload = [];

                foreach ($rows as $row) {
                    $payload[] = [
                        'document_id' => $row->controlled_document_id,
                        'user_id' => $row->user_id,
                        'assigned_by' => $row->assigned_by,
                        'assigned_at' => $row->assigned_at,
                        'completed_at' => $row->completed_at,
                        'completion_comments' => $row->completion_comments,
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ];
                }

                if ($payload !== []) {
                    DB::table('controlled_document_training_assignments')->insert($payload);
                }
            });
    }
};
