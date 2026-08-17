<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Models\EquipmentCalibration;
use App\Domain\QMS\Models\EquipmentCalibrationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EquipmentCalibrationEvent> */
final class EquipmentCalibrationEventFactory extends Factory
{
    protected $model = EquipmentCalibrationEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'equipment_calibration_id' => EquipmentCalibration::factory(),
            'from_status' => EquipmentCalibrationStatus::Scheduled,
            'to_status' => EquipmentCalibrationStatus::InProgress,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
