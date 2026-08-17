<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentCalibrationResult;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_calibrations', function (Blueprint $table) {
            $table->id();
            $table->string('calibration_number')->unique();
            $table->foreignId('equipment_asset_id')->constrained('equipment_assets')->restrictOnDelete();
            $table->string('status')->default(EquipmentCalibrationStatus::Scheduled->value)->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('performed_at')->nullable();
            $table->timestamp('next_due_at')->nullable()->index();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('result')->default(EquipmentCalibrationResult::Pending->value)->index();
            $table->string('certificate_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('deviation_id')->nullable()->constrained('deviations')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_calibrations');
    }
};
