<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentTemplateVariable;
use App\Models\DocumentTemplateVersion;
use App\Models\VariableDataType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTemplateVariable>
 */
class DocumentTemplateVariableFactory extends Factory
{
    protected $model = DocumentTemplateVariable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'template_version_id' => DocumentTemplateVersion::factory(),
            'name' => $name,
            'label' => str($name)->replace('_', ' ')->title()->toString(),
            'variable_data_type_id' => VariableDataType::idFor(VariableDataType::TEXT),
            'default_value' => fake()->optional()->word(),
            'validation_rules' => null,
            'options' => null,
            'required' => false,
        ];
    }
}
