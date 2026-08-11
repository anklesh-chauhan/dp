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

    public const POLICY = 'POLICY';

    public const MANUAL = 'MANUAL';

    public const LOG = 'LOG';

    public const BATCH_RECORD = 'BMR';

    public const BATCH_PACKAGING_RECORD = 'BPR';

    public const FORM = 'FORM';

    public const CHECKLIST = 'CHECKLIST';

    public const ANNEXURE = 'ANNEXURE';

    protected $fillable = [
        'name',
        'code',
        'description',
        'format_profile',
        'execution_workflow',
        'requires_sop_reference',
        'is_issuable',
    ];

    protected function casts(): array
    {
        return [
            'requires_sop_reference' => 'boolean',
            'is_issuable' => 'boolean',
            'execution_workflow' => 'array',
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

    public function requiresExecutionRecord(): bool
    {
        return in_array($this->format_profile, [
            self::FORMAT_CONTROLLED_FORM,
            self::FORMAT_REPEATING_LOG,
            self::FORMAT_CHECKLIST,
        ], true);
    }

    public function isBatchRecord(): bool
    {
        return self::isBatchRecordCode($this->code);
    }

    public function isRepeatingLog(): bool
    {
        return $this->format_profile === self::FORMAT_REPEATING_LOG;
    }

    public function requiresSupervisorReview(): bool
    {
        return (bool) ($this->resolvedExecutionWorkflow()['requires_supervisor_review'] ?? false);
    }

    public static function isBatchRecordCode(?string $code): bool
    {
        return in_array($code, [self::BATCH_RECORD, self::BATCH_PACKAGING_RECORD], true);
    }

    public static function isRepeatingLogCode(?string $code): bool
    {
        return $code === self::LOG;
    }

    /**
     * @return array{requires_item_verification: bool, requires_supervisor_review: bool, requires_qa_approval: bool, requires_disposition: bool}
     */
    public function resolvedExecutionWorkflow(): array
    {
        $batchRecord = $this->isBatchRecord();
        $checklist = $this->format_profile === self::FORMAT_CHECKLIST;

        return array_replace([
            'requires_item_verification' => $batchRecord || $checklist,
            'requires_supervisor_review' => $batchRecord || $checklist || $this->isRepeatingLog(),
            'requires_qa_approval' => $batchRecord,
            'requires_disposition' => $batchRecord,
        ], $this->execution_workflow ?? []);
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
