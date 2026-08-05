<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ControlledDocumentSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledDocumentSection extends Model
{
    /** @use HasFactory<ControlledDocumentSectionFactory> */
    use HasFactory;

    protected $fillable = ['document_id', 'title', 'section_order', 'heading_level', 'content', 'include_in_toc', 'toc_title'];

    protected function casts(): array
    {
        return [
            'section_order' => 'integer',
            'heading_level' => 'integer',
            'include_in_toc' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ControlledDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'document_id');
    }
}
