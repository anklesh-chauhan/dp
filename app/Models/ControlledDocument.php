<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Lockable;
use App\Domain\DMS\Contracts\ControlledDocument as ControlledDocumentContract;
use App\Domain\QMS\Models\QualityAttachment;
use App\Domain\Shared\Contracts\ApprovableSubject;
use Database\Factories\ControlledDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ControlledDocument extends Model implements ApprovableSubject, ControlledDocumentContract
{
    /** @use HasFactory<ControlledDocumentFactory> */
    use HasFactory, Lockable, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (ControlledDocument $document): void {
            $document->document_series_id ??= (string) Str::uuid();
        });
    }

    protected $fillable = [
        'template_id',
        'template_version_id',
        'organization_id',
        'organization_snapshot',
        'document_series_id',
        'supersedes_document_id',
        'document_number',
        'title',
        'version',
        'revision_reason',
        'department_id',
        'category_id',
        'document_type_id',
        'referenced_controlled_document_id',
        'referenced_sop_number',
        'referenced_sop_version',
        'referenced_sop_effective_date',
        'purpose',
        'document_status_id',
        'effective_date',
        'review_date',
        'owner_id',
        'created_by',
        'locked_by',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'review_date' => 'date',
            'referenced_sop_effective_date' => 'date',
            'version' => 'integer',
            'referenced_sop_version' => 'integer',
            'locked_at' => 'datetime',
            'organization_snapshot' => 'array',
        ];
    }

    public function controlledDocumentReference(): string
    {
        return (string) $this->document_number;
    }

    public function controlledDocumentTitle(): string
    {
        return (string) $this->title;
    }

    public function controlledDocumentVersion(): int
    {
        return (int) $this->version;
    }

    public function approvalSubjectKey(): int|string|null
    {
        $key = $this->getKey();

        return is_int($key) || is_string($key) ? $key : null;
    }

    public function approvalSubjectReference(): string
    {
        return $this->controlledDocumentReference();
    }

    public function approvalSubjectTitle(): string
    {
        return $this->controlledDocumentTitle();
    }

    public function approvalSubjectDepartmentId(): ?int
    {
        return $this->department_id === null ? null : (int) $this->department_id;
    }

    public function approvalSubjectCreatedById(): ?int
    {
        return $this->created_by === null ? null : (int) $this->created_by;
    }

    public function approvalSubjectOwnerId(): ?int
    {
        return $this->owner_id === null ? null : (int) $this->owner_id;
    }

    public function isEditable(): bool
    {
        if ($this->isArchivedOrBeyond()) {
            return false;
        }

        return $this->documentStatus?->hasCode(DocumentStatus::DRAFT) ?? false;
    }

    public function isArchivedOrBeyond(): bool
    {
        return in_array($this->documentStatus?->code, DocumentStatus::archivedOrBeyondCodes(), true);
    }

    public function isInRetentionLifecycle(): bool
    {
        return in_array($this->documentStatus?->code, DocumentStatus::retentionLifecycleCodes(), true);
    }

    public function isEffectiveForReference(): bool
    {
        return $this->documentStatus?->hasCode(DocumentStatus::EFFECTIVE) ?? false;
    }

    public function isLockedForEditing(User $user): bool
    {
        if (! $this->isEditable()) {
            return true;
        }

        return $this->isLockedByOther($user);
    }

    public function canBeEditedBy(User $user): bool
    {
        if ($this->isArchivedOrBeyond()) {
            return false;
        }

        return $this->isEditable() && ! $this->isLockedByOther($user);
    }

    public function requiresSopReference(): bool
    {
        return $this->documentType?->requiresSopReference() ?? false;
    }

    public function isIssuableType(): bool
    {
        return $this->documentType?->isIssuableType() ?? false;
    }

    public function hasEffectiveReferencedSop(): bool
    {
        if (! $this->requiresSopReference()) {
            return true;
        }

        if ($this->referenced_controlled_document_id === null) {
            return false;
        }

        return self::query()
            ->whereKey($this->referenced_controlled_document_id)
            ->whereHas('documentStatus', fn (Builder $query): Builder => $query->where('code', DocumentStatus::EFFECTIVE))
            ->exists();
    }

    public function referencedSopIsUnavailable(): bool
    {
        if ($this->referenced_controlled_document_id === null) {
            return true;
        }

        $referencedSop = self::query()
            ->withTrashed()
            ->with('documentStatus')
            ->find($this->referenced_controlled_document_id);

        if ($referencedSop === null) {
            return true;
        }

        if ($referencedSop->trashed()) {
            return true;
        }

        return ! $referencedSop->isEffectiveForReference();
    }

    public function canBeIssued(): bool
    {
        if (! $this->documentStatus?->hasCode(DocumentStatus::EFFECTIVE)) {
            return false;
        }

        if (! $this->isIssuableType()) {
            return false;
        }

        if (! $this->hasEffectiveReferencedSop()) {
            return false;
        }

        return true;
    }

    public function canBePrinted(?DocumentIssuance $issuance = null): bool
    {
        if ($this->isIssuableType()) {
            if (! $this->documentStatus?->hasCode(DocumentStatus::EFFECTIVE)) {
                return false;
            }

            return $issuance !== null && $issuance->isActive() && $issuance->document_id === $this->id;
        }

        return $this->documentStatus?->hasCode(DocumentStatus::APPROVED)
            || $this->documentStatus?->hasCode(DocumentStatus::EFFECTIVE);
    }

    public function canBePrintedDirectly(): bool
    {
        return ! $this->isIssuableType() && $this->canBePrinted();
    }

    /**
     * @param  Builder<ControlledDocument>  $query
     * @return Builder<ControlledDocument>
     */
    public function scopeIssuableDocuments(Builder $query): Builder
    {
        return $query->whereHas('documentType', fn (Builder $typeQuery): Builder => $typeQuery->where('is_issuable', true));
    }

    /**
     * @param  Builder<ControlledDocument>  $query
     * @return Builder<ControlledDocument>
     */
    public function scopeLogDocuments(Builder $query): Builder
    {
        return $query->issuableDocuments();
    }

    /**
     * @return BelongsTo<DocumentTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /**
     * @return BelongsTo<DocumentTemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'template_version_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<ControlledDocument, $this>
     */
    public function supersedesDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_document_id');
    }

    /**
     * @return HasMany<ControlledDocument, $this>
     */
    public function subsequentVersions(): HasMany
    {
        return $this->hasMany(self::class, 'supersedes_document_id');
    }

    /**
     * @return HasMany<ControlledDocument, $this>
     */
    public function versionHistory(): HasMany
    {
        return $this->hasMany(self::class, 'document_series_id', 'document_series_id')
            ->orderByDesc('version');
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
        return $this->belongsTo(DocumentCategory::class);
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * Regulation tags selected for this document at creation time.
     *
     * @return BelongsToMany<RegulationTag, $this>
     */
    public function regulationTags(): BelongsToMany
    {
        return $this->belongsToMany(RegulationTag::class, 'regulation_tag_document');
    }

    /**
     * @return BelongsTo<DocumentStatus, $this>
     */
    public function documentStatus(): BelongsTo
    {
        return $this->belongsTo(DocumentStatus::class);
    }

    /**
     * @return BelongsTo<ControlledDocument, $this>
     */
    public function referencedSop(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referenced_controlled_document_id');
    }

    /**
     * @return HasMany<ControlledDocument, $this>
     */
    public function dependentDocuments(): HasMany
    {
        return $this->hasMany(self::class, 'referenced_controlled_document_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ControlledDocumentSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(ControlledDocumentSection::class, 'document_id')->orderBy('section_order');
    }

    /**
     * @return HasMany<ControlledDocumentVariable, $this>
     */
    public function variables(): HasMany
    {
        return $this->hasMany(ControlledDocumentVariable::class, 'document_id');
    }

    /**
     * @return HasMany<SopApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(SopApproval::class, 'document_id');
    }

    /**
     * @return HasMany<SopAuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(SopAuditLog::class, 'document_id');
    }

    /**
     * @return HasMany<DocumentIssuance, $this>
     */
    public function issuances(): HasMany
    {
        return $this->hasMany(DocumentIssuance::class, 'document_id')->orderByDesc('issued_at');
    }

    /** @return HasMany<ControlledDocumentAccessGrant, $this> */
    public function accessGrants(): HasMany
    {
        return $this->hasMany(ControlledDocumentAccessGrant::class);
    }

    /**
     * @return HasMany<DocumentIssuance, $this>
     */
    public function activeIssuances(): HasMany
    {
        return $this->issuances()->whereHas('issuanceStatus', fn (Builder $query): Builder => $query->where('code', IssuanceStatus::ACTIVE));
    }

    /** @return HasMany<DocumentOriginalArtifact, $this> */
    public function originalArtifacts(): HasMany
    {
        return $this->hasMany(DocumentOriginalArtifact::class);
    }

    /**
     * @return MorphMany<QualityAttachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
