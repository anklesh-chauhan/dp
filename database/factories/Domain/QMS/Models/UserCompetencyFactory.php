<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Models\CompetencyCurriculum;
use App\Domain\QMS\Models\UserCompetency;
use App\Domain\TMS\Enums\UserCompetencyStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserCompetency> */
final class UserCompetencyFactory extends Factory
{
    protected $model = UserCompetency::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'curriculum_id' => CompetencyCurriculum::factory(),
            'status' => UserCompetencyStatus::Assigned,
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
        ];
    }

    public function trained(): static
    {
        return $this->state(fn (): array => [
            'status' => UserCompetencyStatus::Trained,
            'trained_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => UserCompetencyStatus::Expired,
            'trained_at' => now()->subYears(2),
            'expires_at' => now()->subDay(),
        ]);
    }
}
