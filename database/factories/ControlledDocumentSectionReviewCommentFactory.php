<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionReviewComment;
use App\Models\SopApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlledDocumentSectionReviewComment>
 */
class ControlledDocumentSectionReviewCommentFactory extends Factory
{
    protected $model = ControlledDocumentSectionReviewComment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => ControlledDocumentSection::factory(),
            'sop_approval_id' => null,
            'author_id' => User::factory(),
            'body' => fake()->sentence(),
            'resolved_at' => null,
            'resolved_by' => null,
        ];
    }

    public function forSection(ControlledDocumentSection $section): static
    {
        return $this->state(fn (): array => [
            'document_id' => $section->document_id,
            'section_id' => $section->getKey(),
        ]);
    }

    public function forApproval(SopApproval $approval): static
    {
        return $this->state(fn (): array => [
            'sop_approval_id' => $approval->getKey(),
        ]);
    }

    public function resolved(?User $user = null): static
    {
        return $this->state(fn (): array => [
            'resolved_at' => now(),
            'resolved_by' => $user?->getKey() ?? User::factory(),
        ]);
    }
}
