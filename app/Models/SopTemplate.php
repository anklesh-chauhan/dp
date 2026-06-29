<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateStatus;
use Database\Factories\SopTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SopTemplate extends Model
{
    /** @use HasFactory<SopTemplateFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'department_id',
        'category_id',
        'document_type_id',
        'status',
        'current_version',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => TemplateStatus::class,
            'current_version' => 'integer',
        ];
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
            ->where('status', TemplateStatus::Published)
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
