<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NumberSeriesFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberSeries extends Model
{
    /** @use HasFactory<NumberSeriesFactory> */
    use HasFactory;

    protected $fillable = [
        'document_type_id',
        'prefix_pattern',
        'padding_length',
        'suffix',
    ];

    protected function casts(): array
    {
        return [
            'padding_length' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function hasCustomConfiguration(): bool
    {
        return $this->prefix_pattern !== null
            || $this->padding_length !== null
            || $this->suffix !== null;
    }

    public function effectivePrefixPattern(NumberSeriesSetting $settings): string
    {
        return $this->prefix_pattern ?? $settings->default_prefix_pattern;
    }

    public function effectivePaddingLength(NumberSeriesSetting $settings): int
    {
        return $this->padding_length ?? $settings->default_padding_length;
    }

    public function effectiveSuffix(NumberSeriesSetting $settings): string
    {
        return $this->suffix ?? $settings->default_suffix;
    }
}
