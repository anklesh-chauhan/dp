<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Lockable;
use App\Domain\DMS\Enums\TemplateApprovalStatus;
use Database\Factories\SopTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SopTemplate extends Model
{
    public const GENERATION_STATUS_PENDING = 'pending';

    public const GENERATION_STATUS_PROCESSING = 'processing';

    public const GENERATION_STATUS_COMPLETED = 'completed';

    public const GENERATION_STATUS_FAILED = 'failed';

    /** @use HasFactory<SopTemplateFactory> */
    use HasFactory, Lockable, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (SopTemplate $template): void {
            if ($template->document_type_id === null) {
                return;
            }

            $template->category_id = DocumentType::query()
                ->whereKey($template->document_type_id)
                ->value('category_id');
        });
    }

    protected $fillable = [
        'name',
        'code',
        'description',
        'department_id',
        'category_id',
        'document_type_id',
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

        return in_array($this->templateStatus?->code, TemplateStatus::editableCodes(), true);
    }

    public function isArchivedOrBeyond(): bool
    {
        return in_array($this->templateStatus?->code, TemplateStatus::archivedOrBeyondCodes(), true);
    }

    public function isInRetentionLifecycle(): bool
    {
        return in_array($this->templateStatus?->code, TemplateStatus::retentionLifecycleCodes(), true);
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($this->isArchivedOrBeyond()) {
            return false;
        }

        if (! $this->isEditable() || $this->isLockedByOther($user)) {
            return false;
        }

        $draftApprovalStatus = $this->versions()
            ->whereHas('templateStatus', fn ($query) => $query->where('code', TemplateStatus::DRAFT))
            ->latest('version')
            ->value('approval_status');

        if ($draftApprovalStatus === null) {
            return true;
        }

        return ($draftApprovalStatus instanceof TemplateApprovalStatus
            ? $draftApprovalStatus
            : TemplateApprovalStatus::from($draftApprovalStatus)
        )->isEditable();
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

    /**
     * Regulation tags selected for this template at creation time.
     *
     * @return BelongsToMany<RegulationTag, $this>
     */
    public function regulationTags(): BelongsToMany
    {
        return $this->belongsToMany(RegulationTag::class, 'regulation_tag_sop_template');
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
     * @return HasMany<SopTemplateVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(SopTemplateVersion::class);
    }

    /**
     * @return HasOne<SopTemplateVersion, $this>
     */
    public function publishedVersion(): HasOne
    {
        return $this->hasOne(SopTemplateVersion::class)
            ->whereHas('templateStatus', fn ($query) => $query->where('code', TemplateStatus::PUBLISHED))
            ->ofMany('version', 'max');
    }

    /**
     * @return HasOne<SopTemplateVersion, $this>
     */
    public function latestDraftVersion(): HasOne
    {
        return $this->hasOne(SopTemplateVersion::class)
            ->whereHas('templateStatus', fn ($query) => $query->where('code', TemplateStatus::DRAFT))
            ->ofMany('version', 'max');
    }

    /**
     * @return HasMany<SopDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(SopDocument::class, 'template_id');
    }

    /**
     * @return HasMany<SopAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(SopAuditLog::class, 'sop_template_id');
    }

    /**
     * @return HasManyThrough<SopTemplateApprovalEvent, SopTemplateVersion, $this>
     */
    public function approvalEvents(): HasManyThrough
    {
        return $this->hasManyThrough(
            SopTemplateApprovalEvent::class,
            SopTemplateVersion::class,
            'sop_template_id',
            'sop_template_version_id',
        );
    }

    /**
     * @return HasManyThrough<SopTemplateApprovalInstance, SopTemplateVersion, $this>
     */
    public function approvalInstances(): HasManyThrough
    {
        return $this->hasManyThrough(
            SopTemplateApprovalInstance::class,
            SopTemplateVersion::class,
            'sop_template_id',
            'sop_template_version_id',
        );
    }

    /**
     * @return HasManyThrough<SopTemplateSection, SopTemplateVersion, $this>
     */
    public function sections(): HasManyThrough
    {
        return $this->hasManyThrough(SopTemplateSection::class, SopTemplateVersion::class, 'sop_template_id', 'template_version_id');
    }

    /**
     * @return HasManyThrough<SopTemplateVariable, SopTemplateVersion, $this>
     */
    public function variables(): HasManyThrough
    {
        return $this->hasManyThrough(SopTemplateVariable::class, SopTemplateVersion::class, 'sop_template_id', 'template_version_id');
    }
}
