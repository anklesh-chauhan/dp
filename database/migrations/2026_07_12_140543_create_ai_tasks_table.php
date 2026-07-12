<?php

use App\Services\AI\Enums\AiTaskStatus;
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
        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('use_case', 100)->index();

            $table
                ->string('status', 30)
                ->default(AiTaskStatus::PENDING->value)
                ->index();

            $table->json('input');

            $table->json('result')->nullable();

            $table->string('provider', 100)->nullable();

            $table->string('model', 150)->nullable();

            $table
                ->unsignedTinyInteger('progress')
                ->default(0);

            $table
                ->string('current_step', 150)
                ->nullable();

            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamp('failed_at')->nullable();

            $table
                ->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'use_case',
                'status',
            ]);

            $table->index([
                'status',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_tasks');
    }
};
