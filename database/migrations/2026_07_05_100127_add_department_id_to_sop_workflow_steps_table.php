<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sop_workflow_steps', function (Blueprint $table): void {
            $table->foreignId('department_id')
                ->nullable()
                ->after('approval_step_type_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('sop_workflow_steps', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('department_id');
        });
    }
};
