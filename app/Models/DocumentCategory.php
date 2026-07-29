<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    /** @use HasFactory<DocumentCategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code'];

    /**
     * @return HasMany<DocumentTemplate, $this>
     */
    public function sopTemplates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class, 'category_id');
    }
}
