<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ComplaintSource;
use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ComplaintType;
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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number')->unique();
            $table->string('status')->default(ComplaintStatus::Draft->value)->index();
            $table->string('source')->default(ComplaintSource::Other->value)->index();
            $table->string('type')->default(ComplaintType::ProductQuality->value)->index();
            $table->string('title');
            $table->text('description');
            $table->string('external_reference')->nullable();
            $table->timestamp('received_at');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('product_name')->nullable()->index();
            $table->string('batch_number')->nullable()->index();
            $table->string('market_country_code', 2)->nullable()->index();
            $table->boolean('adverse_event_suspected')->nullable()->index();
            $table->boolean('regulatory_reportable')->nullable()->index();
            $table->string('regulatory_authority')->nullable();
            $table->date('regulatory_report_due_at')->nullable();
            $table->timestamp('regulatory_reported_at')->nullable();
            $table->string('regulatory_reference')->nullable();
            $table->date('response_due_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
