<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TemplateStatus;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\SopTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SopTemplate>
 */
class SopTemplateFactory extends Factory
{
    protected $model = SopTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'code' => 'TPL-'.Str::upper(Str::random(6)),
            'description' => fake()->paragraph(),
            'department_id' => Department::factory(),
            'category_id' => DocumentCategory::factory(),
            'document_type_id' => DocumentType::factory(),
            'status' => TemplateStatus::Draft,
            'current_version' => 0,
            'created_by' => User::factory(),
        ];
    }
}
