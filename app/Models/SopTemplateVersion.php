<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Domain\Shared\Contracts\ApprovableSubject;
use Database\Factories\SopTemplateVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SopTemplateVersion extends Model implements ApprovableSubject
{
    /** @use HasFactory<SopTemplateVersionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sop_template_id',
        'version',
        'content_json',
        'effective_date',
        'change_reason',
        'template_status_id',
        'created_by',
        'approval_status',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'effective_date' => 'date',
            'version' => 'integer',
            'approval_status' => TemplateApprovalStatus::class,
            'submitted_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<SopTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(SopTemplate::class, 'sop_template_id');
    }

    /**
     * @return BelongsTo<TemplateStatus, $this>
     */
    public function templateStatus(): BelongsTo
    {
        return $this->belongsTo(TemplateStatus::class);
    }

    /**
     * @return HasMany<SopTemplateSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(SopTemplateSection::class, 'template_version_id')->orderBy('section_order');
    }

    /**
     * @return HasMany<SopTemplateVariable, $this>
     */
    public function variables(): HasMany
    {
        return $this->hasMany(SopTemplateVariable::class, 'template_version_id');
    }

    /**
     * @return HasMany<SopDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(SopDocument::class, 'template_version_id');
    }

    /** @return HasMany<SopTemplateApprovalEvent, $this> */
    public function approvalEvents(): HasMany
    {
        return $this->hasMany(SopTemplateApprovalEvent::class)->orderBy('occurred_at');
    }

    /** @return HasMany<SopTemplateApprovalInstance, $this> */
    public function approvalInstances(): HasMany
    {
        return $this->hasMany(SopTemplateApprovalInstance::class);
    }

    public function approvalSubjectKey(): int|string|null
    {
        $key = $this->getKey();

        return is_int($key) || is_string($key) ? $key : null;
    }

    public function approvalSubjectReference(): string
    {
        return "{$this->template->code} v{$this->version}";
    }

    public function approvalSubjectTitle(): string
    {
        return $this->template->name;
    }

    public function approvalSubjectDepartmentId(): ?int
    {
        return $this->template->department_id;
    }

    public function approvalSubjectCreatedById(): ?int
    {
        return $this->created_by;
    }

    public function approvalSubjectOwnerId(): ?int
    {
        return $this->template->created_by;
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
