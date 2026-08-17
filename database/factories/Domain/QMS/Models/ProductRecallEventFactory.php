<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Models\ProductRecall;
use App\Domain\QMS\Models\ProductRecallEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProductRecallEvent> */
final class ProductRecallEventFactory extends Factory
{
    protected $model = ProductRecallEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'product_recall_id' => ProductRecall::factory(),
            'from_status' => ProductRecallStatus::Draft,
            'to_status' => ProductRecallStatus::Initiated,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
