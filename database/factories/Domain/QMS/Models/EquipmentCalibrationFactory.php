<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentCalibrationResult;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\QMS\Models\EquipmentCalibration;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EquipmentCalibration> */
final class EquipmentCalibrationFactory extends Factory
{
    protected $model = EquipmentCalibration::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'equipment_asset_id' => EquipmentAsset::factory(),
            'status' => EquipmentCalibrationStatus::Scheduled,
            'due_at' => now()->addMonth(),
            'result' => EquipmentCalibrationResult::Pending,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
