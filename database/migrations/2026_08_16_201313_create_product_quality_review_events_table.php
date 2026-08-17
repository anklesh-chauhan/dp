<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_quality_review_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->foreignId('product_quality_review_id')->constrained()->restrictOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->json('context')->nullable();
            $table->string('signature_hash', 64)->nullable();
            $table->string('signature_ip_address', 45)->nullable();
            $table->text('signature_user_agent')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['product_quality_review_id', 'occurred_at'], 'product_quality_review_timeline_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_quality_review_events');
    }
};
