<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\CapaType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capas', function (Blueprint $table) {
            $table->id();
            $table->string('capa_number')->unique();
            $table->foreignId('deviation_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('investigation_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();
            $table->string('type')->default(CapaType::Corrective->value)->index();
            $table->string('status')->default(CapaStatus::Draft->value)->index();
            $table->string('title');
            $table->text('action_plan');
            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->date('due_at');
            $table->timestamp('completed_at')->nullable();
            $table->date('effectiveness_due_at')->nullable();
            $table->timestamp('effectiveness_verified_at')->nullable();
            $table->text('effectiveness_result')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capas');
    }
};
