<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_training_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('designation_id')
                ->constrained('designations')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('training_program_id')
                ->constrained('training_programs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(
                ['designation_id', 'training_program_id'],
                'designation_training_program_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_training_requirements');
    }
};
