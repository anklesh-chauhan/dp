<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Enums\ProductQualityReviewType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_quality_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('review_number')->unique();
            $table->string('type')->default(ProductQualityReviewType::Annual->value)->index();
            $table->string('status')->default(ProductQualityReviewStatus::Draft->value)->index();
            $table->string('title');
            $table->string('product_name')->index();
            $table->string('product_code')->nullable()->index();
            $table->string('dosage_form')->nullable();
            $table->date('period_start_at');
            $table->date('period_end_at');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('input_summary')->nullable();
            $table->longText('conclusions')->nullable();
            $table->longText('recommendations')->nullable();
            $table->longText('yield_summary')->nullable();
            $table->longText('reject_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_quality_reviews');
    }
};
