<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\BatchReleaseStatus;
use App\Domain\QMS\Models\BatchRelease;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BatchRelease> */
final class BatchReleaseFactory extends Factory
{
    protected $model = BatchRelease::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'batch_number' => 'BN-'.fake()->numerify('######'),
            'product_name' => fake()->words(3, true),
            'status' => BatchReleaseStatus::Draft,
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
