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
        Schema::create('sop_template_approval_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sop_template_version_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->uuid('event_uuid')->unique();
            $table->string('from_status');
            $table->string('to_status');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->string('signature_hash', 64)->nullable();
            $table->ipAddress('signature_ip_address')->nullable();
            $table->text('signature_user_agent')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['sop_template_version_id', 'occurred_at'], 'template_approval_event_timeline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sop_template_approval_events');
    }
};
