<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SopTemplateVariable;
use App\Models\SopTemplateVersion;
use App\Models\VariableDataType;
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
        $name = fake()->unique()->word();

        return [
            'template_version_id' => SopTemplateVersion::factory(),
            'name' => $name,
            'label' => str($name)->replace('_', ' ')->title()->toString(),
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::TEXT),
            'default_value' => fake()->optional()->word(),
            'validation_rules' => null,
            'required' => false,
        ];
    }
}
