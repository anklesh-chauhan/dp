<?php

declare(strict_types=1);

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
        Schema::create('product_license_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_license_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('event_type')->index();
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('created_at')->useCurrent();
            $table->index(
                ['product_license_id', 'occurred_at'],
                'license_audit_license_occurred_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_license_audit_events');
    }
};
