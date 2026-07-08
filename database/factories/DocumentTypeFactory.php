<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\RegulationTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentType>
 */
class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Standard Operating Procedure',
            'code' => 'SOP',
            'category_id' => DocumentCategory::factory(),
            'regulation_tags' => RegulationTag::factory(),
            'requires_sop_reference' => false,
            'is_issuable' => false,
        ];
    }
}
