<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ProductReturnDisposition;
use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Models\ProductReturn;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductReturnTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function transition(
        ProductReturn $productReturn,
        ProductReturnStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ProductReturn {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this product return transition.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every product return transition.',
            ]);
        }

        return DB::transaction(function () use ($productReturn, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): ProductReturn {
            $record = ProductReturn::query()->lockForUpdate()->findOrFail($productReturn->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Product return cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $attributes = $this->attributesFor($record, $toStatus, $context);
            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
            $signatureHash = $this->requiresSignature($toStatus)
                ? $this->electronicSignatureHasher->issueFor(
                    signer: $actor,
                    recordKey: $eventUuid,
                    meaning: $toStatus->value,
                    signedAt: $occurredAt,
                    reason: $normalizedReason,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                )
                : null;

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
                'context' => $this->sanitize($context),
                'signature_hash' => $signatureHash,
                'signature_ip_address' => $signatureHash === null ? null : $ipAddress,
                'signature_user_agent' => $signatureHash === null ? null : $userAgent,
                'occurred_at' => $occurredAt,
            ]);

            return $record->refresh();
        });
    }

    /** @return list<ProductReturnStatus> */
    private function allowedFrom(ProductReturnStatus $status): array
    {
        return match ($status) {
            ProductReturnStatus::Draft => [
                ProductReturnStatus::Received,
                ProductReturnStatus::Cancelled,
            ],
            ProductReturnStatus::Received => [
                ProductReturnStatus::UnderQuarantine,
                ProductReturnStatus::Cancelled,
            ],
            ProductReturnStatus::UnderQuarantine => [
                ProductReturnStatus::DispositionPending,
                ProductReturnStatus::Cancelled,
            ],
            ProductReturnStatus::DispositionPending => [
                ProductReturnStatus::Closed,
                ProductReturnStatus::Cancelled,
            ],
            ProductReturnStatus::Closed,
            ProductReturnStatus::Cancelled => [],
        };
    }

    private function permissionFor(ProductReturnStatus $status): string
    {
        return match ($status) {
            ProductReturnStatus::Received => 'Receive:ProductReturn',
            ProductReturnStatus::UnderQuarantine => 'Quarantine:ProductReturn',
            ProductReturnStatus::DispositionPending => 'Dispose:ProductReturn',
            ProductReturnStatus::Closed => 'Close:ProductReturn',
            ProductReturnStatus::Cancelled => 'Manage:ProductReturn',
            ProductReturnStatus::Draft => 'Update:ProductReturn',
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function attributesFor(ProductReturn $record, ProductReturnStatus $toStatus, array $context): array
    {
        $occurredAt = now();

        return match ($toStatus) {
            ProductReturnStatus::Received => [
                'received_at' => $record->received_at ?? $occurredAt,
            ],
            ProductReturnStatus::UnderQuarantine => [
                'quarantined_at' => $record->quarantined_at ?? $occurredAt,
                'disposition' => ProductReturnDisposition::Quarantine,
            ],
            ProductReturnStatus::DispositionPending => [
                'disposition' => $this->resolveDisposition($context),
                'qa_disposition_notes' => $this->resolveQaNotes($context, $record),
                'dispositioned_at' => $record->dispositioned_at ?? $occurredAt,
            ],
            ProductReturnStatus::Closed => $this->attributesForClose($record, $occurredAt),
            default => [],
        };
    }

    /**
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function attributesForClose(ProductReturn $record, mixed $occurredAt): array
    {
        if ($record->disposition === ProductReturnDisposition::Pending) {
            throw ValidationException::withMessages([
                'disposition' => 'A QA disposition decision is required before closing the product return.',
            ]);
        }

        return ['closed_at' => $occurredAt];
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws ValidationException
     */
    private function resolveDisposition(array $context): ProductReturnDisposition
    {
        $raw = $context['disposition'] ?? null;
        $disposition = $raw instanceof ProductReturnDisposition
            ? $raw
            : ProductReturnDisposition::tryFrom((string) $raw);

        if (! $disposition instanceof ProductReturnDisposition || $disposition === ProductReturnDisposition::Pending) {
            throw ValidationException::withMessages([
                'disposition' => 'A QA disposition (quarantine, rework, destroy, or release to stock) is required.',
            ]);
        }

        return $disposition;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveQaNotes(array $context, ProductReturn $record): ?string
    {
        if (! array_key_exists('qa_disposition_notes', $context)) {
            return $record->qa_disposition_notes;
        }

        $notes = trim((string) $context['qa_disposition_notes']);

        return $notes === '' ? null : $notes;
    }

    private function requiresSignature(ProductReturnStatus $status): bool
    {
        return in_array($status, [
            ProductReturnStatus::DispositionPending,
            ProductReturnStatus::Closed,
            ProductReturnStatus::Cancelled,
        ], true);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitize(array $context): array
    {
        unset($context['signature'], $context['payload']);

        if (isset($context['disposition']) && $context['disposition'] instanceof ProductReturnDisposition) {
            $context['disposition'] = $context['disposition']->value;
        }

        return $context;
    }
}
