<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentTemplateSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTemplateSection extends Model
{
    public const TYPE_TEXT = 'rich_text';

    public const TYPE_TABLE = 'structured_table';

    public const TYPE_CHECKLIST = 'checklist';

    public const TYPE_REPEATING_LOG = 'repeating_log';

    public const TYPE_SIGNATURES = 'signatures';

    public const TYPE_ANNEXURES = 'annexures';

    /** @use HasFactory<DocumentTemplateSectionFactory> */
    use HasFactory;

    protected $fillable = [
        'template_version_id',
        'title',
        'section_order',
        'heading_level',
        'section_type',
        'content',
        'configuration',
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
            'configuration' => 'array',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_TEXT => 'Text section',
            self::TYPE_TABLE => 'Structured table',
            self::TYPE_CHECKLIST => 'Checklist',
            self::TYPE_REPEATING_LOG => 'Repeating log',
            self::TYPE_SIGNATURES => 'Signature block',
            self::TYPE_ANNEXURES => 'Attachments / annexures',
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
