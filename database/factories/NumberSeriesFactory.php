<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentType;
use App\Models\NumberSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NumberSeries>
 */
class NumberSeriesFactory extends Factory
{
    protected $model = NumberSeries::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_type_id' => DocumentType::factory(),
            'prefix_pattern' => null,
            'padding_length' => null,
            'suffix' => null,
        ];
    }

    public function withConfiguration(
        ?string $prefixPattern = null,
        ?int $paddingLength = null,
        ?string $suffix = null,
    ): static {
        return $this->state([
            'prefix_pattern' => $prefixPattern,
            'padding_length' => $paddingLength,
            'suffix' => $suffix,
        ]);
    }
}
