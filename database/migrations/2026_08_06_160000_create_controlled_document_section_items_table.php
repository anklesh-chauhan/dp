<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_document_section_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('section_id')->constrained('controlled_document_sections')->cascadeOnDelete();
            $table->unsignedInteger('item_order')->default(1);
            $table->string('label');
            $table->string('response', 100)->nullable();
            $table->text('comments')->nullable();
            $table->boolean('is_required')->default(true);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['section_id', 'item_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_document_section_items');
    }
};
