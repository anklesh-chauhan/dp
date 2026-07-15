<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_executions', function (Blueprint $table): void {
            $table->id();

            $table->ulid('ulid')->unique();

            $table->string('use_case', 100);
            $table->string('capability', 100);
            $table->string('status', 50);

            $table->unsignedSmallInteger('attempt_count')->default(0);

            $table->string('successful_provider', 100)->nullable();
            $table->string('successful_model', 191)->nullable();

            $table->unsignedBigInteger('input_tokens')->nullable();
            $table->unsignedBigInteger('output_tokens')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();

            $table->timestamp('started_at', precision: 6);
            $table->timestamp('completed_at', precision: 6)->nullable();
            $table->timestamp('failed_at', precision: 6)->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('started_at');
            $table->index('successful_provider');
            $table->index([
                'use_case',
                'status',
                'started_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_executions');
    }
};
