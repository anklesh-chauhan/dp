<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Models\ValidationMasterPlan;
use App\Domain\QMS\Models\ValidationMasterPlanEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ValidationMasterPlanEvent> */
final class ValidationMasterPlanEventFactory extends Factory
{
    protected $model = ValidationMasterPlanEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'validation_master_plan_id' => ValidationMasterPlan::factory(),
            'from_status' => ValidationMasterPlanStatus::Draft,
            'to_status' => ValidationMasterPlanStatus::Active,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
