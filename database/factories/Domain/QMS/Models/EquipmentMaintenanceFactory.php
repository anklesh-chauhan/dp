<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\QMS\Models\EquipmentMaintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EquipmentMaintenance> */
final class EquipmentMaintenanceFactory extends Factory
{
    protected $model = EquipmentMaintenance::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'equipment_asset_id' => EquipmentAsset::factory(),
            'status' => EquipmentMaintenanceStatus::Planned,
            'due_at' => now()->addMonth(),
            'description' => fake()->sentence(8),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
