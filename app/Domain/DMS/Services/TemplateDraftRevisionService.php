<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\Shared\Services\AuditLogService;
use App\Enums\ProductModule;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\SopAuditLog;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TemplateDraftRevisionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ModuleManager $moduleManager,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create(DocumentTemplate $template, User $user, string $changeReason): DocumentTemplateVersion
    {
        $this->moduleManager->ensureEnabled(ProductModule::DMS);

        $changeReason = trim($changeReason);

        if ($changeReason === '') {
            throw ValidationException::withMessages([
                'change_reason' => 'A revision reason is required.',
            ]);
        }

        if (! $user->can('revise', $template)) {
            throw new AuthorizationException('You do not have permission to revise document templates.');
        }

        return DB::transaction(function () use ($template, $user, $changeReason): DocumentTemplateVersion {
            $template = DocumentTemplate::query()
                ->lockForUpdate()
                ->findOrFail($template->getKey());

            if ($template->isArchivedOrBeyond() || $template->isInRetentionLifecycle()) {
                throw ValidationException::withMessages([
                    'template' => 'Templates in archive or retention cannot be revised.',
                ]);
            }

            if (! $template->templateStatus?->hasCode(TemplateStatus::PUBLISHED)) {
                throw ValidationException::withMessages([
                    'template' => 'Only published templates can start a draft revision.',
                ]);
            }

            if ($template->latestDraftVersion()->exists()) {
                throw ValidationException::withMessages([
                    'template' => 'This template already has a draft revision.',
                ]);
            }

            $source = $template->publishedVersion()
                ->with(['sections', 'variables'])
                ->first();

            if ($source === null) {
                throw ValidationException::withMessages([
                    'template' => 'A published version is required before creating a draft revision.',
                ]);
            }

            $draft = $template->versions()->create([
                'version' => ((int) $template->current_version) + 1,
                'content_json' => $source->content_json,
                'effective_date' => null,
                'change_reason' => $changeReason,
                'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
                'created_by' => $user->getKey(),
                'approval_status' => TemplateApprovalStatus::Draft,
            ]);

            foreach ($source->sections as $section) {
                $draft->sections()->create($section->only([
                    'title',
                    'section_order',
                    'heading_level',
                    'section_type',
                    'content',
                    'configuration',
                    'is_required',
                    'include_in_toc',
                    'toc_title',
                ]));
            }

            foreach ($source->variables as $variable) {
                $draft->variables()->create($variable->only([
                    'name',
                    'label',
                    'variable_data_type_id',
                    'default_value',
                    'validation_rules',
                    'options',
                    'required',
                ]));
            }

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_VERSION_CREATED,
                oldValues: [
                    'template_version_id' => $source->getKey(),
                    'version' => $source->version,
                ],
                newValues: [
                    'template_id' => $template->getKey(),
                    'template_version_id' => $draft->getKey(),
                    'version' => $draft->version,
                    'status' => TemplateStatus::DRAFT,
                    'change_reason' => $changeReason,
                    'cloned_from_version_id' => $source->getKey(),
                ],
                userId: (int) $user->getKey(),
                template: $template,
            );

            return $draft->refresh()->load(['sections', 'variables', 'templateStatus']);
        });
    }
}
