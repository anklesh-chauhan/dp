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
        Schema::create('quality_approval_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')
                ->constrained('quality_approval_workflows')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedInteger('step_no');
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();

            $table->unique(['workflow_id', 'step_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_approval_workflow_steps');
    }
};
