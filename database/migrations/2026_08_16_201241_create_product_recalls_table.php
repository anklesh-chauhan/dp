<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductRecallClassification;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Enums\ProductRecallType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_recalls', function (Blueprint $table) {
            $table->id();
            $table->string('recall_number')->unique();
            $table->string('type')->default(ProductRecallType::Market->value)->index();
            $table->string('status')->default(ProductRecallStatus::Draft->value)->index();
            $table->string('classification')->default(ProductRecallClassification::NotClassified->value)->index();
            $table->string('title');
            $table->text('description');
            $table->string('product_name')->index();
            $table->string('product_code')->nullable()->index();
            $table->json('batch_numbers')->nullable();
            $table->text('market_countries')->nullable();
            $table->foreignId('complaint_id')->nullable()->constrained('complaints')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('classified_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('effectiveness_verified_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('effectiveness_summary')->nullable();
            $table->boolean('is_mock')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_recalls');
    }
};
