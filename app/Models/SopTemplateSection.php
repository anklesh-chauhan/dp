<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SopTemplateSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopTemplateSection extends Model
{
    /** @use HasFactory<SopTemplateSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'template_version_id',
        'title',
        'section_order',
        'section_type',
        'content',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'section_order' => 'integer',
            'is_required' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<SopTemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(SopTemplateVersion::class, 'template_version_id');
    }
}
