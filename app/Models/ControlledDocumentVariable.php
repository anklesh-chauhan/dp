<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ControlledDocumentVariableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledDocumentVariable extends Model
{
    /** @use HasFactory<ControlledDocumentVariableFactory> */
    use HasFactory;

    protected $fillable = ['document_id', 'variable_name', 'value'];

    /**
     * @return BelongsTo<ControlledDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'document_id');
    }
}
