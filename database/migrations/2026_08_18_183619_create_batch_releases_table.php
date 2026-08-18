<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_releases', function (Blueprint $table): void {
            $table->id();
            $table->string('release_number')->unique();
            $table->string('batch_number');
            $table->string('product_name');
            $table->string('status')->default('draft')->index();
            $table->foreignId('document_execution_id')->nullable()->constrained('document_executions')->nullOnDelete();
            $table->text('disposition_rationale')->nullable();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('released_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('batch_release_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_release_id')->constrained('batch_releases')->restrictOnDelete();
            $table->uuid('event_uuid')->unique();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->json('context')->nullable();
            $table->string('signature_hash')->nullable();
            $table->string('signature_ip_address')->nullable();
            $table->text('signature_user_agent')->nullable();
            $table->timestamp('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_release_events');
        Schema::dropIfExists('batch_releases');
    }
};
