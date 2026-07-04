<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SopTemplateVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SopTemplateVersion extends Model
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
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
            'effective_date' => 'date',
            'version' => 'integer',
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
}
