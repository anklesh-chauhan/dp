<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Models\SupplierQualification;
use App\Domain\QMS\Models\SupplierQualificationEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SupplierQualificationEvent> */
final class SupplierQualificationEventFactory extends Factory
{
    protected $model = SupplierQualificationEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'supplier_qualification_id' => SupplierQualification::factory(),
            'from_status' => SupplierQualificationStatus::Draft,
            'to_status' => SupplierQualificationStatus::UnderAssessment,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
