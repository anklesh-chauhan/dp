<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('ORG-###'),
            'legal_name' => fake()->company(),
            'display_name' => fake()->companySuffix(),
            'registration_number' => fake()->unique()->numerify('REG########'),
            'tax_identifier' => fake()->unique()->numerify('TAX########'),
            'regulatory_identifiers' => ['manufacturing_licence' => fake()->bothify('ML-####')],
            'address_line_1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'IN',
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => fake()->url(),
            'timezone' => 'Asia/Kolkata',
            'is_active' => true,
            'is_default' => false,
        ];
    }
}
