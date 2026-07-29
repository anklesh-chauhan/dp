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
        Schema::create('csv_validation_project_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->foreignId('csv_validation_project_id')->constrained()->restrictOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->json('context')->nullable();
            $table->string('signature_hash', 64)->nullable();
            $table->string('signature_ip_address', 45)->nullable();
            $table->text('signature_user_agent')->nullable();
            $table->timestamp('occurred_at');

            $table->index(['csv_validation_project_id', 'occurred_at'], 'csv_project_event_timeline_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_validation_project_events');
    }
};
