<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ControlledDocumentSectionTableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControlledDocumentSectionTable extends Model
{
    /** @use HasFactory<ControlledDocumentSectionTableFactory> */
    use HasFactory;

    protected $fillable = [
        'section_id',
        'title',
        'table_order',
        'execution_layout',
        'row_count',
    ];

    protected function casts(): array
    {
        return [
            'table_order' => 'integer',
            'row_count' => 'integer',
        ];
    }

    /** @return BelongsTo<ControlledDocumentSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ControlledDocumentSection::class, 'section_id');
    }

    /** @return HasMany<ControlledDocumentSectionItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ControlledDocumentSectionItem::class, 'section_table_id')
            ->orderBy('item_order')
            ->orderBy('id');
    }
}
