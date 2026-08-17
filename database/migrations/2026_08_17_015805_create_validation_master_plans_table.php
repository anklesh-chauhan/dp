<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_master_plans', function (Blueprint $table) {
            $table->id();
            $table->string('vmp_number')->unique();
            $table->string('title');
            $table->string('status')->default(ValidationMasterPlanStatus::Draft->value)->index();
            $table->timestamp('period_start_at')->nullable()->index();
            $table->timestamp('period_end_at')->nullable()->index();
            $table->text('scope')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('controlled_document_id')->nullable()->constrained('controlled_documents')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_master_plans');
    }
};
