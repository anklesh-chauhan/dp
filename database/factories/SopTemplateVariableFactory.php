<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VariableDataType;
use App\Models\SopTemplateVariable;
use App\Models\SopTemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SopTemplateVariable>
 */
class SopTemplateVariableFactory extends Factory
{
    protected $model = SopTemplateVariable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_version_id' => SopTemplateVersion::factory(),
            'name' => fake()->unique()->slug(2),
            'label' => fake()->words(2, true),
            'datatype' => VariableDataType::Text,
            'default_value' => fake()->word(),
            'validation_rules' => null,
            'required' => false,
        ];
    }
}
