<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SopDocumentVariableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SopDocumentVariable extends Model
{
    /** @use HasFactory<SopDocumentVariableFactory> */
    use HasFactory;

    protected $fillable = ['document_id', 'variable_name', 'value'];

    /**
     * @return BelongsTo<SopDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(SopDocument::class, 'document_id');
    }
}
