<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Models\ProductReturn;
use App\Domain\QMS\Models\ProductReturnEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProductReturnEvent> */
final class ProductReturnEventFactory extends Factory
{
    protected $model = ProductReturnEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'product_return_id' => ProductReturn::factory(),
            'from_status' => ProductReturnStatus::Draft,
            'to_status' => ProductReturnStatus::Received,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
