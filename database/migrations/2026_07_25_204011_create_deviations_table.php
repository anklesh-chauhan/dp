<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\DeviationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deviations', function (Blueprint $table) {
            $table->id();
            $table->string('deviation_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->text('immediate_actions')->nullable();
            $table->string('status')->default(DeviationStatus::Draft->value)->index();
            $table->string('severity')->default(DeviationSeverity::Major->value)->index();
            $table->timestamp('occurred_at');
            $table->timestamp('discovered_at');
            $table->foreignId('department_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('reported_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->date('investigation_due_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deviations');
    }
};
