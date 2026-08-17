<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\ManagementReview;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;

final class ManagementReviewPackService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ManagementReviewInputAssembler $inputAssembler,
    ) {}

    /**
     * @return array{
     *     generated_at: string,
     *     review: array{
     *         id: int|string|null,
     *         review_number: string|null,
     *         type: string|null,
     *         status: string|null,
     *         title: string|null,
     *         period_start_at: string|null,
     *         period_end_at: string|null,
     *         scheduled_at: string|null,
     *         required_inputs: list<string>,
     *         input_summary: string|null,
     *         decisions: string|null,
     *         action_summary: string|null
     *     },
     *     assembled_inputs: list<array{title: string, summary: string, stats: array<string, int>}>,
     *     assembled_inputs_markdown: string,
     *     attachment_count: int
     * }
     */
    public function build(ManagementReview $review, User $actor): array
    {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('View:QualityMetrics')) {
            throw new AuthorizationException('You do not have permission to download a management review pack.');
        }

        $sections = $this->inputAssembler->assemble($review, $actor);

        return [
            'generated_at' => now()->toIso8601String(),
            'review' => [
                'id' => $review->getKey(),
                'review_number' => $review->review_number,
                'type' => $review->type?->value,
                'status' => $review->status?->value,
                'title' => $review->title,
                'period_start_at' => $review->period_start_at?->toDateString(),
                'period_end_at' => $review->period_end_at?->toDateString(),
                'scheduled_at' => $review->scheduled_at?->toIso8601String(),
                'required_inputs' => array_values(array_filter(
                    $review->required_inputs ?? [],
                    static fn (mixed $value): bool => is_string($value),
                )),
                'input_summary' => $review->input_summary,
                'decisions' => $review->decisions,
                'action_summary' => $review->action_summary,
            ],
            'assembled_inputs' => $sections,
            'assembled_inputs_markdown' => $this->inputAssembler->toMarkdown($sections),
            'attachment_count' => $review->attachments()->count(),
        ];
    }
}
