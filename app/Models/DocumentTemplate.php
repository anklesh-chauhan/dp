<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Lockable;
use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\Shared\Services\CurrentPendingApprovalStepResolver;
use App\Domain\Shared\Support\PendingApprovalStep;
use Database\Factories\DocumentTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends Model
{
    public const GENERATION_STATUS_PENDING = 'pending';

    public const GENERATION_STATUS_PROCESSING = 'processing';

    public const GENERATION_STATUS_COMPLETED = 'completed';

    public const GENERATION_STATUS_FAILED = 'failed';

    /** @use HasFactory<DocumentTemplateFactory> */
    use HasFactory, Lockable, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'department_id',
        'category_id',
        'document_type_id',
        'report_template_id',
        'template_status_id',
        'current_version',
        'created_by',
        'locked_by',
        'locked_at',
        'generation_status',
        'generation_progress',
    ];

    protected function casts(): array
    {
        return [
            'current_version' => 'integer',
            'locked_at' => 'datetime',
        ];
    }

    public function isGenerationInProgress(): bool
    {
        if (in_array($this->generation_status, [
            self::GENERATION_STATUS_PENDING,
            self::GENERATION_STATUS_COMPLETED,
            self::GENERATION_STATUS_FAILED,
        ], true)) {
            return false;
        }

        return $this->generation_progress < 100;
    }

    public function isEditable(): bool
    {
        if ($this->isArchivedOrBeyond()) {
            return false;
        }

        $draft = $this->latestDraftVersion()->first();

        if ($draft === null) {
            return $this->templateStatus?->hasCode(TemplateStatus::DRAFT) ?? false;
        }

        return $draft->approval_status->isEditable();
    }

    public function isArchivedOrBeyond(): bool
    {
        return in_array($this->templateStatus?->code, TemplateStatus::archivedOrBeyondCodes(), true);
    }

    public function isInRetentionLifecycle(): bool
    {
        return in_array($this->templateStatus?->code, TemplateStatus::retentionLifecycleCodes(), true);
    }

    /**
     * Workflow-aware status for UI badges. Lifecycle stays Draft/Published;
     * while a draft version is in approval, surface that instead of Draft,
     * including the current pending workflow step when available.
     */
    public function displayStatusLabel(): string
    {
        $draft = $this->relationLoaded('latestDraftVersion')
            ? $this->latestDraftVersion
            : $this->latestDraftVersion()->first();

        if ($draft instanceof DocumentTemplateVersion) {
            $label = match ($draft->approval_status) {
                TemplateApprovalStatus::Submitted,
                TemplateApprovalStatus::Reviewed,
                TemplateApprovalStatus::Approved,
                TemplateApprovalStatus::Rejected => $draft->approval_status->label(),
                default => $this->templateStatus?->name
                    ?? TemplateApprovalStatus::Draft->label(),
            };

            if (in_array($draft->approval_status, [
                TemplateApprovalStatus::Submitted,
                TemplateApprovalStatus::Reviewed,
            ], true)) {
                $pending = $this->currentPendingApprovalStep();

                if ($pending instanceof PendingApprovalStep) {
                    return $pending->withStatusLabel($label);
                }
            }

            return $label;
        }

        return $this->templateStatus?->name ?? 'Unknown';
    }

    public function currentPendingApprovalStep(): ?PendingApprovalStep
    {
        return app(CurrentPendingApprovalStepResolver::class)->forDocumentTemplate($this);
    }

    public function displayStatusColor(): string
    {
        $draft = $this->relationLoaded('latestDraftVersion')
            ? $this->latestDraftVersion
            : $this->latestDraftVersion()->first();

        if ($draft instanceof DocumentTemplateVersion) {
            return match ($draft->approval_status) {
                TemplateApprovalStatus::Submitted,
                TemplateApprovalStatus::Reviewed => 'warning',
                TemplateApprovalStatus::Approved => 'success',
                TemplateApprovalStatus::Rejected => 'danger',
                default => $this->lifecycleStatusColor(),
            };
        }

        return $this->lifecycleStatusColor();
    }

    private function lifecycleStatusColor(): string
    {
        return match ($this->templateStatus?->code) {
            TemplateStatus::PUBLISHED => 'success',
            TemplateStatus::OBSOLETE => 'warning',
            TemplateStatus::DESTROYED => 'danger',
            default => 'gray',
        };
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($this->isArchivedOrBeyond() || $this->isLockedByOther($user)) {
            return false;
        }

        return $this->isEditable();
    }

    public function canStartDraftRevisionBy(User $user): bool
    {
        if ($this->isArchivedOrBeyond() || $this->isInRetentionLifecycle() || $this->isLockedByOther($user)) {
            return false;
        }

        if (! $this->templateStatus?->hasCode(TemplateStatus::PUBLISHED)) {
            return false;
        }

        if ($this->latestDraftVersion()->exists()) {
            return false;
        }

        return $user->can('revise', $this);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<DocumentCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /** @return BelongsTo<ReportTemplate, $this> */
    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class);
    }

    /**
     * Regulation tags selected for this template at creation time.
     *
     * @return BelongsToMany<RegulationTag, $this>
     */
    public function regulationTags(): BelongsToMany
    {
        return $this->belongsToMany(RegulationTag::class, 'regulation_tag_document_template');
    }

    /**
     * @return BelongsTo<TemplateStatus, $this>
     */
    public function templateStatus(): BelongsTo
    {
        return $this->belongsTo(TemplateStatus::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<DocumentTemplateVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentTemplateVersion::class);
    }

    /**
     * @return HasOne<DocumentTemplateVersion, $this>
     */
    public function publishedVersion(): HasOne
    {
        return $this->hasOne(DocumentTemplateVersion::class)
            ->whereHas('templateStatus', fn ($query) => $query->where('code', TemplateStatus::PUBLISHED))
            ->ofMany('version', 'max');
    }

    /**
     * @return HasOne<DocumentTemplateVersion, $this>
     */
    public function latestDraftVersion(): HasOne
    {
        return $this->hasOne(DocumentTemplateVersion::class)
            ->whereHas('templateStatus', fn ($query) => $query->where('code', TemplateStatus::DRAFT))
            ->ofMany('version', 'max');
    }

    /**
     * @return HasMany<ControlledDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ControlledDocument::class, 'template_id');
    }

    /**
     * @return HasMany<SopAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(SopAuditLog::class, 'document_template_id');
    }

    /**
     * @return HasManyThrough<DocumentTemplateApprovalEvent, DocumentTemplateVersion, $this>
     */
    public function approvalEvents(): HasManyThrough
    {
        return $this->hasManyThrough(
            DocumentTemplateApprovalEvent::class,
            DocumentTemplateVersion::class,
            'document_template_id',
            'document_template_version_id',
        );
    }

    /**
     * @return HasManyThrough<DocumentTemplateApprovalInstance, DocumentTemplateVersion, $this>
     */
    public function approvalInstances(): HasManyThrough
    {
        return $this->hasManyThrough(
            DocumentTemplateApprovalInstance::class,
            DocumentTemplateVersion::class,
            'document_template_id',
            'document_template_version_id',
        );
    }

    /**
     * @return HasManyThrough<DocumentTemplateSection, DocumentTemplateVersion, $this>
     */
    public function sections(): HasManyThrough
    {
        return $this->hasManyThrough(DocumentTemplateSection::class, DocumentTemplateVersion::class, 'document_template_id', 'template_version_id');
    }

    /**
     * @return HasManyThrough<DocumentTemplateVariable, DocumentTemplateVersion, $this>
     */
    public function variables(): HasManyThrough
    {
        return $this->hasManyThrough(DocumentTemplateVariable::class, DocumentTemplateVersion::class, 'document_template_id', 'template_version_id');
    }
}
