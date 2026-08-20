<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ProductRecallClassification;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Models\ProductRecall;
use App\Domain\Shared\Services\ContentBoundElectronicSignatureIssuer;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductRecallTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ContentBoundElectronicSignatureIssuer $contentBoundSignatures,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function transition(
        ProductRecall $productRecall,
        ProductRecallStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ProductRecall {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this product recall transition.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every product recall transition.',
            ]);
        }

        return DB::transaction(function () use ($productRecall, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): ProductRecall {
            $record = ProductRecall::query()->lockForUpdate()->findOrFail($productRecall->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Product recall cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $attributes = $this->attributesFor($record, $toStatus, $context);
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
                ...$attributes,
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

    /** @return list<ProductRecallStatus> */
    private function allowedFrom(ProductRecallStatus $status): array
    {
        return match ($status) {
            ProductRecallStatus::Draft => [
                ProductRecallStatus::Initiated,
                ProductRecallStatus::Cancelled,
            ],
            ProductRecallStatus::Initiated => [
                ProductRecallStatus::RiskClassified,
                ProductRecallStatus::Cancelled,
            ],
            ProductRecallStatus::RiskClassified => [
                ProductRecallStatus::NotificationInProgress,
                ProductRecallStatus::Cancelled,
            ],
            ProductRecallStatus::NotificationInProgress => [
                ProductRecallStatus::ExecutionInProgress,
                ProductRecallStatus::Cancelled,
            ],
            ProductRecallStatus::ExecutionInProgress => [
                ProductRecallStatus::EffectivenessCheck,
                ProductRecallStatus::Cancelled,
            ],
            ProductRecallStatus::EffectivenessCheck => [
                ProductRecallStatus::Closed,
                ProductRecallStatus::Cancelled,
            ],
            ProductRecallStatus::Closed,
            ProductRecallStatus::Cancelled => [],
        };
    }

    private function permissionFor(ProductRecallStatus $status): string
    {
        return match ($status) {
            ProductRecallStatus::Initiated => 'Initiate:ProductRecall',
            ProductRecallStatus::RiskClassified => 'Classify:ProductRecall',
            ProductRecallStatus::NotificationInProgress => 'Notify:ProductRecall',
            ProductRecallStatus::ExecutionInProgress => 'Execute:ProductRecall',
            ProductRecallStatus::EffectivenessCheck => 'Verify:ProductRecall',
            ProductRecallStatus::Closed => 'Close:ProductRecall',
            ProductRecallStatus::Cancelled => 'Manage:ProductRecall',
            ProductRecallStatus::Draft => 'Update:ProductRecall',
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function attributesFor(ProductRecall $record, ProductRecallStatus $toStatus, array $context): array
    {
        $occurredAt = now();

        return match ($toStatus) {
            ProductRecallStatus::Initiated => [
                'initiated_at' => $record->initiated_at ?? $occurredAt,
            ],
            ProductRecallStatus::RiskClassified => [
                'classification' => $this->resolveClassification($context),
                'classified_at' => $record->classified_at ?? $occurredAt,
            ],
            ProductRecallStatus::NotificationInProgress => [
                'notified_at' => $record->notified_at ?? $occurredAt,
            ],
            ProductRecallStatus::ExecutionInProgress => [
                'executed_at' => $record->executed_at ?? $occurredAt,
            ],
            ProductRecallStatus::EffectivenessCheck => [
                'effectiveness_summary' => $this->resolveEffectivenessSummary($context, $record),
                'effectiveness_verified_at' => $record->effectiveness_verified_at ?? $occurredAt,
            ],
            ProductRecallStatus::Closed => [
                'closed_at' => $occurredAt,
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws ValidationException
     */
    private function resolveClassification(array $context): ProductRecallClassification
    {
        $raw = $context['classification'] ?? null;
        $classification = $raw instanceof ProductRecallClassification
            ? $raw
            : ProductRecallClassification::tryFrom((string) $raw);

        if (! $classification instanceof ProductRecallClassification || $classification === ProductRecallClassification::NotClassified) {
            throw ValidationException::withMessages([
                'classification' => 'A Class I, II, or III classification is required before continuing the recall.',
            ]);
        }

        return $classification;
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws ValidationException
     */
    private function resolveEffectivenessSummary(array $context, ProductRecall $record): string
    {
        $summary = trim((string) ($context['effectiveness_summary'] ?? $record->effectiveness_summary ?? ''));

        if ($summary === '') {
            throw ValidationException::withMessages([
                'effectiveness_summary' => 'An effectiveness summary is required before verifying the recall.',
            ]);
        }

        return $summary;
    }

    private function requiresSignature(ProductRecallStatus $status): bool
    {
        return in_array($status, [
            ProductRecallStatus::RiskClassified,
            ProductRecallStatus::EffectivenessCheck,
            ProductRecallStatus::Closed,
            ProductRecallStatus::Cancelled,
        ], true);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitize(array $context): array
    {
        unset($context['signature'], $context['payload']);

        if (isset($context['classification']) && $context['classification'] instanceof ProductRecallClassification) {
            $context['classification'] = $context['classification']->value;
        }

        return $context;
    }
}
