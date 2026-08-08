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
        Schema::create('document_execution_deviations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_execution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deviation_id')->constrained('deviations')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['document_execution_id', 'deviation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_execution_deviations');
    }
};
