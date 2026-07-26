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
        Schema::create('quality_approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('workflow_code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('subject_type')->index();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['subject_type', 'department_id', 'is_active'], 'quality_workflow_selection_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_approval_workflows');
    }
};
