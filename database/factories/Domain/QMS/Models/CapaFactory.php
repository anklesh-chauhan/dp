<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\CapaType;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Deviation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Capa>
 */
class CapaFactory extends Factory
{
    protected $model = Capa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deviation_id' => Deviation::factory(),
            'investigation_id' => null,
            'type' => CapaType::CorrectiveAndPreventive,
            'status' => CapaStatus::Draft,
            'title' => fake()->sentence(5),
            'action_plan' => fake()->paragraph(),
            'owner_id' => User::factory(),
            'due_at' => today()->addDays(45),
            'completed_at' => null,
            'effectiveness_due_at' => today()->addDays(90),
            'effectiveness_verified_at' => null,
            'effectiveness_result' => null,
            'closed_at' => null,
        ];
    }
}
