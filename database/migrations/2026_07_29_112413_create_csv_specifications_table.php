<?php

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
        Schema::create('csv_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csv_validation_project_id')->constrained()->cascadeOnDelete();
            $table->string('specification_identifier');
            $table->unsignedInteger('version')->default(1);
            $table->string('type')->index();
            $table->string('title');
            $table->text('description');
            $table->string('status')->index();
            $table->foreignId('controlled_document_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['csv_validation_project_id', 'specification_identifier', 'version'],
                'csv_spec_project_identifier_version_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_specifications');
    }
};
