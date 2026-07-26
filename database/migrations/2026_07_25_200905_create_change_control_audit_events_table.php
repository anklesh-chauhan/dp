<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_control_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('change_control_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('from_status');
            $table->string('to_status');
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(
                ['change_control_id', 'occurred_at'],
                'cc_audit_timeline_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_control_audit_events');
    }
};
