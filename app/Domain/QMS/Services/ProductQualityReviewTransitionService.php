<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Domain\Shared\Services\ContentBoundElectronicSignatureIssuer;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

final class ProductQualityReviewTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ContentBoundElectronicSignatureIssuer $contentBoundSignatures,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function transition(
        ProductQualityReview $review,
        ProductQualityReviewStatus $toStatus,
        User $actor,
        string $reason,
        ?string $inputSummary = null,
        ?string $conclusions = null,
        ?string $recommendations = null,
        ?string $yieldSummary = null,
        ?string $rejectSummary = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ProductQualityReview {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this product quality review transition.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every product quality review transition.',
            ]);
        }

        return DB::transaction(function () use (
            $review,
            $toStatus,
            $actor,
            $normalizedReason,
            $inputSummary,
            $conclusions,
            $recommendations,
            $yieldSummary,
            $rejectSummary,
            $context,
            $ipAddress,
            $userAgent,
        ): ProductQualityReview {
            $record = ProductQualityReview::query()->lockForUpdate()->findOrFail($review->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Product quality review cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $updates = $this->validatedUpdates(
                $record,
                $toStatus,
                $actor,
                $inputSummary,
                $conclusions,
                $recommendations,
                $yieldSummary,
                $rejectSummary,
            );
            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
            $eventContext = $this->sanitize($context);
            $signatureHash = null;
            if ($this->requiresSignature($toStatus)) {
                [$signatureHash, $eventContext] = $this->contentBoundSignatures->issue(
                    signer: $actor,
                    subject: $record,
                    recordKey: $eventUuid,
                    meaning: $toStatus->value,
                    signedAt: $occurredAt,
                    reason: $normalizedReason,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    context: $eventContext,
                );
            }

            $record->update([
                'status' => $toStatus,
                ...$updates,
                ...($toStatus === ProductQualityReviewStatus::InProgress
                    && $record->started_at === null ? ['started_at' => $occurredAt] : []),
                ...($toStatus === ProductQualityReviewStatus::Approved ? [
                    'approved_by' => $actor->getKey(),
                    'approved_at' => $occurredAt,
                ] : []),
                ...($toStatus === ProductQualityReviewStatus::Closed ? [
                    'closed_at' => $occurredAt,
                ] : []),
            ]);
            $record->auditEvents()->create([
                'event_uuid' => $eventUuid,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_id' => $actor->getKey(),
                'reason' => $normalizedReason,
                'context' => $eventContext,
                'signature_hash' => $signatureHash,
                'signature_ip_address' => $signatureHash === null ? null : $ipAddress,
                'signature_user_agent' => $signatureHash === null ? null : $userAgent,
                'occurred_at' => $occurredAt,
            ]);

            return $record->refresh();
        });
    }

    /**
     * Best-effort period counts of quality events matching the PQR product name.
     *
     * @return array{deviations: int, complaints: int, change_controls: int}
     */
    public function periodInputCounts(ProductQualityReview $review): array
    {
        $productName = trim($review->product_name);
        $periodStart = $review->period_start_at?->startOfDay();
        $periodEnd = $review->period_end_at?->endOfDay();

        if ($productName === '' || $periodStart === null || $periodEnd === null) {
            return [
                'deviations' => 0,
                'complaints' => 0,
                'change_controls' => 0,
            ];
        }

        return [
            'deviations' => Deviation::query()
                ->whereBetween('occurred_at', [$periodStart, $periodEnd])
                ->where(function ($query) use ($productName): void {
                    $query->where('title', 'like', "%{$productName}%")
                        ->orWhere('description', 'like', "%{$productName}%");
                })
                ->count(),
            'complaints' => Complaint::query()
                ->whereBetween('received_at', [$periodStart, $periodEnd])
                ->where('product_name', $productName)
                ->count(),
            'change_controls' => ChangeControl::query()
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->where(function ($query) use ($productName): void {
                    $query->where('title', 'like', "%{$productName}%")
                        ->orWhere('description', 'like', "%{$productName}%");
                })
                ->count(),
        ];
    }

    /**
     * Stub: CAPA / Change Control spawn from PQR recommendations will be wired in a later slice
     * once Filament create-with-context actions are defined for those resources.
     */
    public function spawnFollowUpFromRecommendations(ProductQualityReview $review, User $actor): never
    {
        throw new LogicException(
            'Spawning CAPA or Change Control from Product Quality Review recommendations is not implemented yet.'
        );
    }

    /** @return list<ProductQualityReviewStatus> */
    private function allowedFrom(ProductQualityReviewStatus $status): array
    {
        return match ($status) {
            ProductQualityReviewStatus::Draft => [
                ProductQualityReviewStatus::InProgress,
                ProductQualityReviewStatus::Cancelled,
            ],
            ProductQualityReviewStatus::InProgress => [
                ProductQualityReviewStatus::UnderReview,
                ProductQualityReviewStatus::Cancelled,
            ],
            ProductQualityReviewStatus::UnderReview => [
                ProductQualityReviewStatus::Approved,
                ProductQualityReviewStatus::InProgress,
                ProductQualityReviewStatus::Cancelled,
            ],
            ProductQualityReviewStatus::Approved => [
                ProductQualityReviewStatus::Closed,
            ],
            ProductQualityReviewStatus::Closed,
            ProductQualityReviewStatus::Cancelled => [],
        };
    }

    private function permissionFor(ProductQualityReviewStatus $status): string
    {
        return match ($status) {
            ProductQualityReviewStatus::InProgress => 'Conduct:ProductQualityReview',
            ProductQualityReviewStatus::UnderReview => 'Conduct:ProductQualityReview',
            ProductQualityReviewStatus::Approved => 'Approve:ProductQualityReview',
            ProductQualityReviewStatus::Closed => 'Close:ProductQualityReview',
            ProductQualityReviewStatus::Cancelled => 'Manage:ProductQualityReview',
            ProductQualityReviewStatus::Draft => 'Update:ProductQualityReview',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedUpdates(
        ProductQualityReview $review,
        ProductQualityReviewStatus $toStatus,
        User $actor,
        ?string $inputSummary,
        ?string $conclusions,
        ?string $recommendations,
        ?string $yieldSummary,
        ?string $rejectSummary,
    ): array {
        if ($toStatus === ProductQualityReviewStatus::InProgress && $review->status === ProductQualityReviewStatus::Draft) {
            if ($review->period_end_at->lt($review->period_start_at)
                || blank($review->product_name)
                || blank($review->title)
                || $review->owner_id === null) {
                throw ValidationException::withMessages([
                    'owner_id' => 'A coherent review period, product name, title, and owner are required to begin the PQR.',
                ]);
            }
        }

        if ($toStatus === ProductQualityReviewStatus::UnderReview) {
            $normalizedInputSummary = trim((string) ($inputSummary ?? $review->input_summary));
            if ($normalizedInputSummary === '') {
                throw ValidationException::withMessages([
                    'input_summary' => 'An input summary is required before submitting the PQR for review.',
                ]);
            }

            $updates = ['input_summary' => $normalizedInputSummary];

            if ($conclusions !== null) {
                $updates['conclusions'] = trim($conclusions);
            }
            if ($recommendations !== null) {
                $updates['recommendations'] = trim($recommendations);
            }
            if ($yieldSummary !== null) {
                $updates['yield_summary'] = trim($yieldSummary);
            }
            if ($rejectSummary !== null) {
                $updates['reject_summary'] = trim($rejectSummary);
            }

            return $updates;
        }

        if ($toStatus === ProductQualityReviewStatus::Approved) {
            $normalizedConclusions = trim((string) ($conclusions ?? $review->conclusions));
            $normalizedRecommendations = trim((string) ($recommendations ?? $review->recommendations));

            if (blank($review->input_summary) && $inputSummary === null) {
                throw ValidationException::withMessages([
                    'input_summary' => 'Reviewed inputs are required before approval.',
                ]);
            }

            if ($normalizedConclusions === '' || $normalizedRecommendations === '') {
                throw ValidationException::withMessages([
                    'conclusions' => 'Conclusions and recommendations are required before approval.',
                ]);
            }

            if (in_array((int) $actor->getKey(), array_filter([
                $review->created_by,
                $review->owner_id,
            ]), true)) {
                throw ValidationException::withMessages([
                    'approved_by' => 'The creator or owner cannot independently approve this product quality review.',
                ]);
            }

            return [
                'input_summary' => trim((string) ($inputSummary ?? $review->input_summary)),
                'conclusions' => $normalizedConclusions,
                'recommendations' => $normalizedRecommendations,
                ...($yieldSummary !== null ? ['yield_summary' => trim($yieldSummary)] : []),
                ...($rejectSummary !== null ? ['reject_summary' => trim($rejectSummary)] : []),
            ];
        }

        return [];
    }

    private function requiresSignature(ProductQualityReviewStatus $status): bool
    {
        return in_array($status, [
            ProductQualityReviewStatus::Approved,
            ProductQualityReviewStatus::Closed,
            ProductQualityReviewStatus::Cancelled,
        ], true);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitize(array $context): array
    {
        unset($context['signature'], $context['payload']);

        return $context;
    }
}
