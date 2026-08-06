<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_document_deviations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('controlled_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deviation_id')->constrained('deviations')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['controlled_document_id', 'deviation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_document_deviations');
    }
};
