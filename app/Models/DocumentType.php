<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    public const FORMAT_TEXT_DOCUMENT = 'text_document';

    public const FORMAT_STRUCTURED_TABLE = 'structured_table';

    public const FORMAT_CONTROLLED_FORM = 'controlled_form';

    public const FORMAT_REPEATING_LOG = 'repeating_log';

    public const FORMAT_CHECKLIST = 'checklist';

    public const FORMAT_ATTACHMENT_PACKAGE = 'attachment_package';

    use HasFactory;

    public const SOP = 'SOP';

    public const LOG = 'LOG';

    public const BATCH_RECORD = 'BMR';

    public const FORM = 'FORM';

    protected $fillable = [
        'name',
        'code',
        'description',
        'format_profile',
        'requires_sop_reference',
        'is_issuable',
    ];

    protected function casts(): array
    {
        return [
            'requires_sop_reference' => 'boolean',
            'is_issuable' => 'boolean',
        ];
    }

    /**
     * @return HasMany<DocumentTemplate, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class);
    }

    /**
     * @return HasMany<ControlledDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ControlledDocument::class);
    }

    /**
     * @return BelongsToMany<RegulationTag, $this>
     */
    public function regulationTags(): BelongsToMany
    {
        return $this->belongsToMany(RegulationTag::class, 'regulation_tag_document_type');
    }

    public function requiresSopReference(): bool
    {
        return $this->requires_sop_reference;
    }

    public function isIssuableType(): bool
    {
        return $this->is_issuable;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return static::query()->orderBy('name')->pluck('name', 'id')->toArray();
    }

    /**
     * @return array<string, string>
     */
    public static function formatProfileOptions(): array
    {
        return [
            self::FORMAT_TEXT_DOCUMENT => 'Text document',
            self::FORMAT_STRUCTURED_TABLE => 'Structured table',
            self::FORMAT_CONTROLLED_FORM => 'Controlled form',
            self::FORMAT_REPEATING_LOG => 'Repeating log',
            self::FORMAT_CHECKLIST => 'Checklist',
            self::FORMAT_ATTACHMENT_PACKAGE => 'Attachment package',
        ];
    }
}
