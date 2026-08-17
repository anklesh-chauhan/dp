<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Enums\EquipmentQualificationType;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\QMS\Models\EquipmentQualification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EquipmentQualification> */
final class EquipmentQualificationFactory extends Factory
{
    protected $model = EquipmentQualification::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'equipment_asset_id' => EquipmentAsset::factory(),
            'type' => fake()->randomElement(EquipmentQualificationType::cases()),
            'status' => EquipmentQualificationStatus::Draft,
            'protocol_title' => fake()->sentence(4),
            'protocol_summary' => fake()->optional()->paragraph(),
            'acceptance_criteria' => fake()->optional()->paragraph(),
        ];
    }
}
