<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\UserCompetencyStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_competencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('curriculum_id')
                ->constrained('competency_curricula')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('status')->default(UserCompetencyStatus::Assigned->value)->index();
            $table->timestamp('trained_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'curriculum_id']);
            $table->index(['curriculum_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_competencies');
    }
};
