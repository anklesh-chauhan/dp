<?php

declare(strict_types=1);

namespace App\Domain\DMS\Actions;

use App\Data\ControlledDocumentData;
use App\Enums\ProductModule;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentDraftSession;
use App\Models\User;
use App\Services\AI\Enums\ControlledDocumentDraftSessionStatus;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class CreateControlledDocumentFromAiDraftAction
{
    public function __construct(
        private CreateDocumentFromTemplateAction $createDocument,
        private ModuleManager $moduleManager,
    ) {}

    public function execute(
        ControlledDocumentDraftSession $session,
        User $user,
        string $expectedPreviewHash,
    ): ControlledDocument {
        $this->moduleManager->ensureEnabled(ProductModule::AI);
        Gate::forUser($user)->authorize('create', ControlledDocument::class);

        return DB::transaction(function () use ($session, $user, $expectedPreviewHash): ControlledDocument {
            $lockedSession = ControlledDocumentDraftSession::query()
                ->with(['template.regulationTags', 'templateVersion', 'controlledDocument'])
                ->lockForUpdate()
                ->findOrFail($session->getKey());

            if ((int) $lockedSession->created_by !== (int) $user->getKey()) {
                throw ValidationException::withMessages([
                    'session' => 'You cannot confirm another user’s document draft.',
                ]);
            }

            if ($lockedSession->controlledDocument !== null) {
                return $lockedSession->controlledDocument;
            }

            if ($lockedSession->status !== ControlledDocumentDraftSessionStatus::PREVIEW_READY) {
                throw ValidationException::withMessages([
                    'session' => 'Complete the document preview before creating the draft.',
                ]);
            }

            if (
                blank($lockedSession->preview_hash)
                || ! hash_equals((string) $lockedSession->preview_hash, $expectedPreviewHash)
                || ! hash_equals($lockedSession->calculatePreviewHash(), $expectedPreviewHash)
            ) {
                throw ValidationException::withMessages([
                    'session' => 'The preview changed. Review the latest version before confirming it.',
                ]);
            }

            $document = $this->createDocument->execute(new ControlledDocumentData(
                templateId: (int) $lockedSession->template_id,
                templateVersionId: (int) $lockedSession->template_version_id,
                title: (string) $lockedSession->title,
                ownerId: (int) $lockedSession->owner_id,
                createdBy: (int) $user->getKey(),
                variables: $lockedSession->draft_variables ?? [],
                regulationTagIds: $lockedSession->template->regulationTags
                    ->modelKeys(),
                referencedControlledDocumentId: $lockedSession->referenced_controlled_document_id,
                purpose: (string) data_get($lockedSession->brief, 'purpose', ''),
            ));

            $lockedSession->forceFill([
                'status' => ControlledDocumentDraftSessionStatus::CONFIRMED,
                'confirmed_at' => now(),
                'controlled_document_id' => $document->getKey(),
            ])->save();

            return $document;
        });
    }
}
