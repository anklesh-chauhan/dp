<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\DMS\Services\DocumentRevisionService;
use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Enums\DocumentImpactAction;
use App\Domain\QMS\Models\ChangeControlDocumentImpact;
use App\Enums\ProductModule;
use App\Models\ControlledDocument;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApprovedChangeControlDocumentRevisionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly DocumentRevisionService $documentRevisionService,
        private readonly ChangeControlTransitionService $transitionService,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(
        ChangeControlDocumentImpact $documentImpact,
        User $user,
    ): ControlledDocument {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $user->can('Implement:ChangeControl')) {
            throw new AuthorizationException(
                'You do not have permission to implement change controls.',
            );
        }

        return DB::transaction(function () use ($documentImpact, $user): ControlledDocument {
            $impact = ChangeControlDocumentImpact::query()
                ->with(['changeControl', 'sourceDocument', 'resultDocument'])
                ->lockForUpdate()
                ->findOrFail($documentImpact->getKey());

            if ($impact->resultDocument !== null) {
                return $impact->resultDocument;
            }

            if ($impact->changeControl->status !== ChangeControlStatus::Approved) {
                throw ValidationException::withMessages([
                    'change_control' => 'Only approved change controls can create document revisions.',
                ]);
            }

            if (
                $impact->required_action !== DocumentImpactAction::Revise
                || $impact->sourceDocument === null
            ) {
                throw ValidationException::withMessages([
                    'document_impact' => 'The document impact must require revision of a source document.',
                ]);
            }

            $revision = $this->documentRevisionService->create(
                $impact->sourceDocument,
                $user,
                "Change Control {$impact->changeControl->change_number}: {$impact->rationale}",
            );

            $impact->update(['result_document_id' => $revision->getKey()]);
            $this->transitionService->transition(
                $impact->changeControl,
                ChangeControlStatus::Implementing,
                $user,
                'Document revision implementation started.',
                ['document_impact_id' => $impact->getKey()],
            );

            return $revision;
        });
    }
}
