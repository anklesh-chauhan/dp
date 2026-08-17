<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ProductReturnDisposition;
use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Models\ProductReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductReturn> */
final class ProductReturnFactory extends Factory
{
    protected $model = ProductReturn::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'status' => ProductReturnStatus::Draft,
            'disposition' => ProductReturnDisposition::Pending,
            'product_name' => fake()->words(3, true),
            'batch_number' => fake()->bothify('BATCH-####'),
            'quantity' => fake()->randomFloat(3, 1, 500),
            'unit' => 'units',
            'reason' => fake()->sentence(),
            'source' => fake()->optional()->randomElement(['customer', 'distributor', 'warehouse']),
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
