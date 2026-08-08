<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledDocumentSectionItem extends Model
{
    public const VALUE_TEXT = 'text';

    public const VALUE_NUMERIC = 'numeric';

    public const VALUE_BOOLEAN = 'boolean';

    protected $fillable = [
        'section_id',
        'item_order',
        'label',
        'value_type',
        'unit',
        'decimal_precision',
        'acceptance_operator',
        'acceptance_min',
        'acceptance_max',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'item_order' => 'integer',
            'is_required' => 'boolean',
            'decimal_precision' => 'integer',
            'acceptance_min' => 'decimal:8',
            'acceptance_max' => 'decimal:8',
        ];
    }

    /** @return BelongsTo<ControlledDocumentSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ControlledDocumentSection::class, 'section_id');
    }
}
