<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Models\EquipmentMaintenance;
use App\Domain\QMS\Models\EquipmentMaintenanceEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EquipmentMaintenanceEvent> */
final class EquipmentMaintenanceEventFactory extends Factory
{
    protected $model = EquipmentMaintenanceEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'equipment_maintenance_id' => EquipmentMaintenance::factory(),
            'from_status' => EquipmentMaintenanceStatus::Planned,
            'to_status' => EquipmentMaintenanceStatus::InProgress,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
