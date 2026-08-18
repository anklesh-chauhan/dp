<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\KnowledgeLesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KnowledgeLesson> */
final class KnowledgeLessonFactory extends Factory
{
    protected $model = KnowledgeLesson::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'summary' => fake()->sentence(12),
            'body' => fake()->paragraphs(2, true),
            'created_by' => User::factory(),
            'is_published' => true,
        ];
    }
}
