<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatus;
use Database\Factories\SopDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SopDocument extends Model
{
    /** @use HasFactory<SopDocumentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'template_id',
        'template_version_id',
        'document_number',
        'title',
        'version',
        'department_id',
        'status',
        'effective_date',
        'review_date',
        'owner_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'effective_date' => 'date',
            'review_date' => 'date',
            'version' => 'integer',
        ];
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
}
