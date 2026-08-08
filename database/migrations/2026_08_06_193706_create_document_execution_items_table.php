<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_execution_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('document_execution_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_item_id')->nullable()->constrained('controlled_document_section_items')->nullOnDelete();
            $table->unsignedInteger('item_order')->default(1);
            $table->dateTime('scheduled_at')->nullable();
            $table->string('label');
            $table->string('value_type', 30)->default('text');
            $table->string('unit', 30)->nullable();
            $table->unsignedTinyInteger('decimal_precision')->nullable();
            $table->string('acceptance_operator', 20)->nullable();
            $table->decimal('acceptance_min', 20, 8)->nullable();
            $table->decimal('acceptance_max', 20, 8)->nullable();
            $table->string('response', 100)->nullable();
            $table->string('result_status', 20)->nullable();
            $table->text('comments')->nullable();
            $table->boolean('is_required')->default(true);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['document_execution_section_id', 'item_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_execution_items');
    }
};
