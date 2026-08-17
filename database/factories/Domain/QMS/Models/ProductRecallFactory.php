<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ProductRecallClassification;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Enums\ProductRecallType;
use App\Domain\QMS\Models\ProductRecall;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductRecall> */
final class ProductRecallFactory extends Factory
{
    protected $model = ProductRecall::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => ProductRecallType::Market,
            'status' => ProductRecallStatus::Draft,
            'classification' => ProductRecallClassification::NotClassified,
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'product_name' => fake()->words(3, true),
            'product_code' => fake()->optional()->bothify('SKU-####'),
            'batch_numbers' => [fake()->bothify('BATCH-####')],
            'market_countries' => fake()->optional()->country(),
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
            'is_mock' => false,
        ];
    }

    public function mock(): static
    {
        return $this->state(fn (): array => [
            'type' => ProductRecallType::Mock,
            'is_mock' => true,
        ]);
    }
}
