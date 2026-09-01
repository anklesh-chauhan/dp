<?php

declare(strict_types=1);

namespace Database\Factories\Domain\TMS\Models;

use App\Domain\TMS\Enums\UserCompetencyStatus;
use App\Domain\TMS\Models\CompetencyCurriculum;
use App\Domain\TMS\Models\UserCompetency;
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
}
