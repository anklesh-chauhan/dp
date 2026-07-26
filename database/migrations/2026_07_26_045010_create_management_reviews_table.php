<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Enums\ManagementReviewType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('management_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('review_number')->unique();
            $table->string('type')->default(ManagementReviewType::Annual->value)->index();
            $table->string('status')->default(ManagementReviewStatus::Draft->value)->index();
            $table->string('title');
            $table->date('period_start_at');
            $table->date('period_end_at');
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('held_at')->nullable();
            $table->foreignId('chair_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('required_inputs');
            $table->longText('input_summary')->nullable();
            $table->longText('decisions')->nullable();
            $table->longText('action_summary')->nullable();
            $table->timestamp('minutes_issued_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('management_reviews');
    }
};
