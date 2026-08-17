<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentAssetStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\QMS\Models\EquipmentAssetEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EquipmentAssetEvent> */
final class EquipmentAssetEventFactory extends Factory
{
    protected $model = EquipmentAssetEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'equipment_asset_id' => EquipmentAsset::factory(),
            'from_status' => EquipmentAssetStatus::Active,
            'to_status' => EquipmentAssetStatus::Inactive,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
