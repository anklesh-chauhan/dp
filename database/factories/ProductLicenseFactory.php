<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductLicense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductLicense>
 */
class ProductLicenseFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $licenseKey = fake()->unique()->uuid();

        return [
            'license_key' => $licenseKey,
            'key_id' => 'test-key',
            'payload' => json_encode([
                'license_key' => $licenseKey,
                'modules' => ['dms'],
            ], JSON_THROW_ON_ERROR),
            'signature' => base64_encode(fake()->sha256()),
            'activated_at' => now(),
            'issued_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
            'grace_ends_at' => now()->addYear()->addDays(14),
            'revoked_at' => null,
            'last_verified_at' => null,
        ];
    }
}
