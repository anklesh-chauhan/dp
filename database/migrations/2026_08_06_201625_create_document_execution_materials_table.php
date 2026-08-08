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
        Schema::create('document_execution_materials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_execution_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('material_order')->default(1);
            $table->string('material_name');
            $table->string('material_code', 100)->nullable();
            $table->string('lot_number', 100)->nullable();
            $table->decimal('planned_quantity', 20, 8)->nullable();
            $table->decimal('actual_quantity', 20, 8)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['document_execution_id', 'material_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_execution_materials');
    }
};
