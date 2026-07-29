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
        Schema::create('csv_periodic_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('csv_validation_project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('review_no');
            $table->date('due_date');
            $table->date('reviewed_on')->nullable();
            $table->json('review_scope');
            $table->text('findings')->nullable();
            $table->text('validation_conclusion')->nullable();
            $table->boolean('revalidation_required')->nullable();
            $table->date('next_review_date')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['csv_validation_project_id', 'review_no'],
                'csv_periodic_review_project_number_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_periodic_reviews');
    }
};
