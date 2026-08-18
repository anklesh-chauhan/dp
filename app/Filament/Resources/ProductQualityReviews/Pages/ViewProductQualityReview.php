<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductQualityReviews\Pages;

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Services\ProductQualityReviewTransitionService;
use App\Filament\Resources\ProductQualityReviews\ProductQualityReviewResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Filament\Support\CaptureKnowledgeLessonAction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

final class ViewProductQualityReview extends ViewRecord
{
    protected static string $resource = ProductQualityReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => ProductQualityReviewResource::canEdit($this->record)),
            CaptureKnowledgeLessonAction::make(fn (): Model => $this->record),
            $this->transitionAction(
                'begin',
                'Begin Review',
                ProductQualityReviewStatus::InProgress,
                [ProductQualityReviewStatus::Draft],
                'Conduct:ProductQualityReview',
            ),
            $this->transitionAction(
                'submitReview',
                'Submit for Review',
                ProductQualityReviewStatus::UnderReview,
                [ProductQualityReviewStatus::InProgress],
                'Conduct:ProductQualityReview',
                requireInputSummary: true,
            ),
            $this->transitionAction(
                'returnToProgress',
                'Return to In Progress',
                ProductQualityReviewStatus::InProgress,
                [ProductQualityReviewStatus::UnderReview],
                'Conduct:ProductQualityReview',
            ),
            $this->transitionAction(
                'approve',
                'Approve',
                ProductQualityReviewStatus::Approved,
                [ProductQualityReviewStatus::UnderReview],
                'Approve:ProductQualityReview',
                'success',
                requireConclusions: true,
            ),
            $this->transitionAction(
                'close',
                'Close',
                ProductQualityReviewStatus::Closed,
                [ProductQualityReviewStatus::Approved],
                'Close:ProductQualityReview',
                'success',
            ),
            $this->transitionAction(
                'cancel',
                'Cancel',
                ProductQualityReviewStatus::Cancelled,
                [
                    ProductQualityReviewStatus::Draft,
                    ProductQualityReviewStatus::InProgress,
                    ProductQualityReviewStatus::UnderReview,
                ],
                'Manage:ProductQualityReview',
                'danger',
            ),
        ];
    }

    /** @param list<ProductQualityReviewStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        ProductQualityReviewStatus $toStatus,
        array $fromStatuses,
        string $permission,
        string $color = 'primary',
        bool $requireInputSummary = false,
        bool $requireConclusions = false,
    ): Action {
        $schema = [];

        if ($requireInputSummary) {
            $schema[] = Textarea::make('input_summary')
                ->required(fn (): bool => blank($this->record->input_summary))
                ->default(fn () => $this->record->input_summary)
                ->rows(4);
        }

        if ($requireConclusions) {
            $schema[] = Textarea::make('conclusions')
                ->required(fn (): bool => blank($this->record->conclusions))
                ->default(fn () => $this->record->conclusions)
                ->rows(4);
            $schema[] = Textarea::make('recommendations')
                ->required(fn (): bool => blank($this->record->recommendations))
                ->default(fn () => $this->record->recommendations)
                ->rows(4);
        }

        $schema[] = ApprovalNarrativeTextarea::decisionRationale(
            name: 'reason',
            label: 'Decision reason',
            helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
            context: fn (): array => [
                'record_type' => 'Product Quality Review lifecycle decision',
                'subject' => $this->record->review_number ?? (string) $this->record->getKey(),
                'decision' => $label,
            ],
        );

        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema($schema)
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(ProductQualityReviewTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    inputSummary: $data['input_summary'] ?? null,
                    conclusions: $data['conclusions'] ?? null,
                    recommendations: $data['recommendations'] ?? null,
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'input_summary',
                    'conclusions',
                    'recommendations',
                    'yield_summary',
                    'reject_summary',
                    'started_at',
                    'approved_at',
                    'closed_at',
                ]);

                Notification::make()->success()->title("Product Quality Review: {$label}")->send();
            });
    }
}
