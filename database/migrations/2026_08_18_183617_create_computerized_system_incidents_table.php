<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('computerized_system_incidents', function (Blueprint $table): void {
            $table->id();
            $table->string('incident_number')->unique();
            $table->string('title');
            $table->string('status')->default('open')->index();
            $table->string('severity')->default('minor')->index();
            $table->string('category')->default('availability');
            $table->text('description');
            $table->text('impact')->nullable();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('computerized_system_incident_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('incident_id')->constrained('computerized_system_incidents')->restrictOnDelete();
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
        Schema::dropIfExists('computerized_system_incident_events');
        Schema::dropIfExists('computerized_system_incidents');
    }
};
