<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ComplaintSource;
use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ComplaintType;
use App\Domain\QMS\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Complaint> */
final class ComplaintFactory extends Factory
{
    protected $model = Complaint::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'status' => ComplaintStatus::Draft,
            'source' => ComplaintSource::Patient,
            'type' => ComplaintType::ProductQuality,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'external_reference' => fake()->optional()->bothify('EXT-####'),
            'received_at' => now(),
            'received_by' => User::factory(),
            'department_id' => Department::factory(),
            'owner_id' => User::factory(),
            'product_name' => fake()->words(3, true),
            'batch_number' => fake()->bothify('BATCH-####'),
            'market_country_code' => fake()->countryCode(),
            'adverse_event_suspected' => null,
            'regulatory_reportable' => null,
            'response_due_at' => today()->addDays(30),
        ];
    }
}
