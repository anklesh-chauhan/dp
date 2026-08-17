<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Domain\QMS\Models\LaboratoryOosEventEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<LaboratoryOosEventEvent> */
final class LaboratoryOosEventEventFactory extends Factory
{
    protected $model = LaboratoryOosEventEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'laboratory_oos_event_id' => LaboratoryOosEvent::factory(),
            'from_status' => LaboratoryOosStatus::Draft,
            'to_status' => LaboratoryOosStatus::PhaseOne,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
