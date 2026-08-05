<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentTemplateSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplateSection extends Model
{
    /** @use HasFactory<DocumentTemplateSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'template_version_id',
        'title',
        'section_order',
        'heading_level',
        'section_type',
        'content',
        'is_required',
        'include_in_toc',
        'toc_title',
    ];

    protected function casts(): array
    {
        return [
            'section_order' => 'integer',
            'heading_level' => 'integer',
            'is_required' => 'boolean',
            'include_in_toc' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DocumentTemplateVersion, $this>
     */
    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplateVersion::class, 'template_version_id');
    }
}
