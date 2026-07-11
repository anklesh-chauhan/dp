<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeGuide;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeGuide>
 */
class KnowledgeGuideFactory extends Factory
{
    protected $model = KnowledgeGuide::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title),
            'summary' => fake()->paragraph(),
            'content' => "# {$title}\n\n".fake()->paragraphs(3, true),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => [
            'is_published' => false,
        ]);
    }
}
