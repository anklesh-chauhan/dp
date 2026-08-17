<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('csv_signed_decisions', function (Blueprint $table) {
            $table->id();
            $table->uuid('decision_uuid')->unique();
            $table->foreignId('csv_validation_project_id')->constrained()->restrictOnDelete();
            $table->morphs('subject');
            $table->string('decision_code');
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->json('context')->nullable();
            $table->string('signature_hash', 64);
            $table->string('signature_ip_address', 45)->nullable();
            $table->text('signature_user_agent')->nullable();
            $table->timestamp('occurred_at');

            $table->index(['csv_validation_project_id', 'occurred_at'], 'csv_signed_decision_timeline_index');
            $table->index(['decision_code', 'occurred_at'], 'csv_signed_decision_code_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csv_signed_decisions');
    }
};
