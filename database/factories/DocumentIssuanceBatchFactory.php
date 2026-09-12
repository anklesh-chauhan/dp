<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ControlledDocument;
use App\Models\DocumentIssuanceBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentIssuanceBatch>
 */
class DocumentIssuanceBatchFactory extends Factory
{
    protected $model = DocumentIssuanceBatch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => ControlledDocument::factory(),
            'issued_by' => User::factory(),
            'copy_count' => 1,
            'pack_status' => DocumentIssuanceBatch::PACK_PENDING,
        ];
    }
}
