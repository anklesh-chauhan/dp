<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Domain\Shared\Services\ContentBoundElectronicSignatureIssuer;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SiteMasterFileTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ContentBoundElectronicSignatureIssuer $contentBoundSignatures,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, array{content?: string|null, document_id?: int|null}>|null  $sections
     */
    public function transition(
        SiteMasterFile $file,
        SiteMasterFileStatus $toStatus,
        User $actor,
        string $reason,
        ?array $sections = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): SiteMasterFile {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this site master file transition.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every site master file transition.',
            ]);
        }

        return DB::transaction(function () use (
            $file,
            $toStatus,
            $actor,
            $normalizedReason,
            $sections,
            $context,
            $ipAddress,
            $userAgent,
        ): SiteMasterFile {
            $record = SiteMasterFile::query()->lockForUpdate()->findOrFail($file->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Site master file cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $this->assertReadyFor($record, $toStatus, $actor, $sections);

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

            $updates = [
                'status' => $toStatus,
            ];

            if ($sections !== null) {
                $updates['sections'] = $this->mergeSections($record->sections ?? [], $sections);
            }

            if ($toStatus === SiteMasterFileStatus::Published) {
                $updates['published_by'] = $actor->getKey();
                $updates['published_at'] = $occurredAt;
                $updates['version'] = max(1, (int) $record->version);
            }

            $record->update($updates);

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

    /** @return list<SiteMasterFileStatus> */
    private function allowedFrom(SiteMasterFileStatus $status): array
    {
        return match ($status) {
            SiteMasterFileStatus::Draft => [
                SiteMasterFileStatus::InReview,
            ],
            SiteMasterFileStatus::InReview => [
                SiteMasterFileStatus::Published,
                SiteMasterFileStatus::Draft,
            ],
            SiteMasterFileStatus::Published => [
                SiteMasterFileStatus::Retired,
            ],
            SiteMasterFileStatus::Retired => [],
        };
    }

    private function permissionFor(SiteMasterFileStatus $status): string
    {
        return match ($status) {
            SiteMasterFileStatus::InReview => 'Update:SiteMasterFile',
            SiteMasterFileStatus::Published => 'Publish:SiteMasterFile',
            SiteMasterFileStatus::Retired => 'Retire:SiteMasterFile',
            SiteMasterFileStatus::Draft => 'Update:SiteMasterFile',
        };
    }

    /**
     * @param  array<string, array{content?: string|null, document_id?: int|null}>|null  $sections
     */
    private function assertReadyFor(
        SiteMasterFile $file,
        SiteMasterFileStatus $toStatus,
        User $actor,
        ?array $sections,
    ): void {
        if ($toStatus === SiteMasterFileStatus::InReview) {
            if (blank($file->title) || $file->owner_id === null) {
                throw ValidationException::withMessages([
                    'owner_id' => 'Title and owner are required before submitting the site master file for review.',
                ]);
            }
        }

        if ($toStatus === SiteMasterFileStatus::Published) {
            $merged = $this->mergeSections($file->sections ?? [], $sections ?? []);
            $siteInfo = trim((string) ($merged['site_information']['content'] ?? ''));

            if ($siteInfo === '') {
                throw ValidationException::withMessages([
                    'sections' => 'Site information content is required before publishing the site master file.',
                ]);
            }

            if (in_array((int) $actor->getKey(), array_filter([
                $file->created_by,
                $file->owner_id,
            ]), true)) {
                throw ValidationException::withMessages([
                    'published_by' => 'The creator or owner cannot independently publish this site master file.',
                ]);
            }
        }
    }

    private function requiresSignature(SiteMasterFileStatus $status): bool
    {
        return in_array($status, [
            SiteMasterFileStatus::Published,
            SiteMasterFileStatus::Retired,
        ], true);
    }

    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, array{content?: string|null, document_id?: int|null}>  $incoming
     * @return array<string, array{content: string|null, document_id: int|null}>
     */
    private function mergeSections(array $existing, array $incoming): array
    {
        $base = $existing !== [] ? $existing : SiteMasterFile::defaultSections();

        foreach ($incoming as $key => $section) {
            $base[$key] = [
                'content' => array_key_exists('content', $section)
                    ? ($section['content'] === null ? null : trim((string) $section['content']))
                    : ($base[$key]['content'] ?? null),
                'document_id' => array_key_exists('document_id', $section)
                    ? $section['document_id']
                    : ($base[$key]['document_id'] ?? null),
            ];
        }

        /** @var array<string, array{content: string|null, document_id: int|null}> $base */
        return $base;
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
