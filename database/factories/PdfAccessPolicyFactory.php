<?php

namespace Database\Factories;

use App\Models\PdfAccessPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PdfAccessPolicy> */
class PdfAccessPolicyFactory extends Factory
{
    protected $model = PdfAccessPolicy::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'effect' => PdfAccessPolicy::EFFECT_ALLOW,
            'priority' => 100,
            'can_view' => true,
            'can_print' => false,
            'can_download' => false,
            'is_active' => true,
        ];
    }
}
