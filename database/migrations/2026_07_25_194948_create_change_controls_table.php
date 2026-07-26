<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_controls', function (Blueprint $table) {
            $table->id();
            $table->string('change_number')->unique();
            $table->string('title');
            $table->text('description');
            $table->text('rationale');
            $table->string('status')->default(ChangeControlStatus::Draft->value)->index();
            $table->foreignId('department_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->date('implementation_due_at')->nullable();
            $table->timestamp('implemented_at')->nullable();
            $table->date('effectiveness_due_at')->nullable();
            $table->timestamp('effectiveness_verified_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_controls');
    }
};
