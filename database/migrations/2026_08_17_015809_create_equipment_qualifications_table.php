<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_qualifications', function (Blueprint $table) {
            $table->id();
            $table->string('qualification_number')->unique();
            $table->foreignId('equipment_asset_id')->constrained('equipment_assets')->restrictOnDelete();
            $table->string('type')->index();
            $table->string('status')->default(EquipmentQualificationStatus::Draft->value)->index();
            $table->string('protocol_title');
            $table->text('protocol_summary')->nullable();
            $table->text('acceptance_criteria')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deviation_id')->nullable()->constrained('deviations')->nullOnDelete();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_qualifications');
    }
};
