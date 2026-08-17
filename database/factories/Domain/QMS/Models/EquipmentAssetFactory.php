<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentAssetCategory;
use App\Domain\QMS\Enums\EquipmentAssetCriticality;
use App\Domain\QMS\Enums\EquipmentAssetStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EquipmentAsset> */
final class EquipmentAssetFactory extends Factory
{
    protected $model = EquipmentAsset::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'asset_tag' => fake()->bothify('TAG-####'),
            'location' => fake()->optional()->city(),
            'category' => fake()->randomElement(EquipmentAssetCategory::cases()),
            'criticality' => fake()->randomElement(EquipmentAssetCriticality::cases()),
            'gxp_impact' => fake()->boolean(),
            'status' => EquipmentAssetStatus::Active,
            'owner_id' => User::factory(),
            'manufacturer' => fake()->optional()->company(),
            'model' => fake()->optional()->bothify('MDL-###'),
            'serial_number' => fake()->optional()->bothify('SN########'),
        ];
    }
}
