<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Lockable;
use App\Enums\ControlledDocumentTypeCode;
use App\Enums\DocumentStatus;
use App\Enums\IssuanceStatus;
use Database\Factories\SopDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SopDocument extends Model
{
    /** @use HasFactory<SopDocumentFactory> */
    use HasFactory, Lockable, SoftDeletes;

    protected $fillable = [
        'template_id',
        'template_version_id',
        'document_number',
        'title',
        'version',
        'department_id',
        'document_type_id',
        'referenced_sop_document_id',
        'referenced_sop_number',
        'referenced_sop_version',
        'referenced_sop_effective_date',
        'batch_number',
        'product_name',
        'purpose',
        'status',
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
            'status' => DocumentStatus::class,
            'effective_date' => 'date',
            'review_date' => 'date',
            'referenced_sop_effective_date' => 'date',
            'version' => 'integer',
            'referenced_sop_version' => 'integer',
            'locked_at' => 'datetime',
        ];
    }

    public function isEditable(): bool
    {
        return $this->status === DocumentStatus::Draft;
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

    public function canBeIssued(): bool
    {
        if ($this->status !== DocumentStatus::Effective) {
            return false;
        }

        if (! $this->isIssuableType()) {
            return false;
        }

        if ($this->requiresSopReference() && $this->referenced_sop_document_id === null) {
            return false;
        }

        return true;
    }

    public function canBePrinted(?DocumentIssuance $issuance = null): bool
    {
        if ($this->isIssuableType()) {
            if ($this->status !== DocumentStatus::Effective) {
                return false;
            }

            return $issuance !== null && $issuance->isActive() && $issuance->document_id === $this->id;
        }

        return in_array($this->status, [DocumentStatus::Approved, DocumentStatus::Effective], true);
    }

    /**
     * @param  Builder<SopDocument>  $query
     * @return Builder<SopDocument>
     */
    public function scopeIssuableDocuments(Builder $query): Builder
    {
        return $query->whereHas('documentType', function (Builder $typeQuery): void {
            $typeQuery->whereIn('code', array_map(
                fn (ControlledDocumentTypeCode $type): string => $type->value,
                ControlledDocumentTypeCode::issuableTypes(),
            ));
        });
    }

    /**
     * @param  Builder<SopDocument>  $query
     * @return Builder<SopDocument>
     */
    public function scopeLogDocuments(Builder $query): Builder
    {
        return $query->issuableDocuments();
    }

    /**
     * @return BelongsTo<SopTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(SopTemplate::class, 'template_id');
    }

    /**
     * @return BelongsTo<SopTemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(SopTemplateVersion::class, 'template_version_id');
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<SopDocument, $this>
     */
    public function referencedSop(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referenced_sop_document_id');
    }

    /**
     * @return HasMany<SopDocument, $this>
     */
    public function dependentDocuments(): HasMany
    {
        return $this->hasMany(self::class, 'referenced_sop_document_id');
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
     * @return HasMany<SopDocumentSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(SopDocumentSection::class, 'document_id')->orderBy('section_order');
    }

    /**
     * @return HasMany<SopDocumentVariable, $this>
     */
    public function variables(): HasMany
    {
        return $this->hasMany(SopDocumentVariable::class, 'document_id');
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

    /**
     * @return HasMany<DocumentIssuance, $this>
     */
    public function activeIssuances(): HasMany
    {
        return $this->issuances()->where('status', IssuanceStatus::Active);
    }
}
