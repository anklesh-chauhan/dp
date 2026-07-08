<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RegulationTag extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'icon',
    ];

    /**
     * @return BelongsToMany<DocumentType, $this>
     */
    public function documentTypes(): BelongsToMany
    {
        return $this->belongsToMany(DocumentType::class, 'regulation_tag_document_type');
    }
}
