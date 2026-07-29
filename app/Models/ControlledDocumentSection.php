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

    protected $fillable = ['document_id', 'title', 'section_order', 'content'];

    protected function casts(): array
    {
        return [
            'section_order' => 'integer',
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
