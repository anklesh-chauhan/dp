<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_execution_attempts', function (Blueprint $table): void {
            $table->id();

            $table
                ->foreignId('ai_execution_id')
                ->constrained('ai_executions')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('sequence');

            $table->string('provider', 100);
            $table->string('model', 191);
            $table->string('status', 50);

            $table->unsignedBigInteger('input_tokens')->nullable();
            $table->unsignedBigInteger('output_tokens')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();

            $table->string('exception_class')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('started_at', precision: 6);
            $table->timestamp('completed_at', precision: 6)->nullable();
            $table->timestamp('failed_at', precision: 6)->nullable();

            $table->timestamps();

            $table->unique([
                'ai_execution_id',
                'sequence',
            ]);

            $table->index([
                'provider',
                'status',
                'started_at',
            ]);

            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_execution_attempts');
    }
};
