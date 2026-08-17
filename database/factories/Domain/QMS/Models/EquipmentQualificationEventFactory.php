<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Models\EquipmentQualification;
use App\Domain\QMS\Models\EquipmentQualificationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EquipmentQualificationEvent> */
final class EquipmentQualificationEventFactory extends Factory
{
    protected $model = EquipmentQualificationEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'equipment_qualification_id' => EquipmentQualification::factory(),
            'from_status' => EquipmentQualificationStatus::Draft,
            'to_status' => EquipmentQualificationStatus::InProgress,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
