<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductLicenseAuditEventType;
use App\Enums\ProductLicenseState;
use App\Models\ProductLicense;
use App\Models\ProductLicenseAuditEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductLicenseAuditEvent>
 */
class ProductLicenseAuditEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_license_id' => ProductLicense::factory(),
            'event_type' => ProductLicenseAuditEventType::Activated,
            'from_state' => null,
            'to_state' => ProductLicenseState::Active,
            'context' => ['key_id' => 'test-key'],
            'occurred_at' => now(),
        ];
    }
}
