<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code'];

    /**
     * @return HasMany<SopTemplate, $this>
     */
    public function sopTemplates(): HasMany
    {
        return $this->hasMany(SopTemplate::class);
    }
}
