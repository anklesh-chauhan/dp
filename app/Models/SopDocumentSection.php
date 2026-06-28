<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SopDocumentSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopDocumentSection extends Model
{
    /** @use HasFactory<SopDocumentSectionFactory> */
    use HasFactory;

    protected $fillable = ['document_id', 'title', 'section_order', 'content'];

    protected function casts(): array
    {
        return [
            'section_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<SopDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(SopDocument::class, 'document_id');
    }
}
