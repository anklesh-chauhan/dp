<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ControlledDocumentTypeCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    /**
     * @return HasMany<SopTemplate, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(SopTemplate::class);
    }

    /**
     * @return HasMany<SopDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(SopDocument::class);
    }

    public function requiresSopReference(): bool
    {
        $code = ControlledDocumentTypeCode::tryFrom($this->code);

        return $code?->requiresSopReference() ?? false;
    }

    public function isIssuableType(): bool
    {
        $code = ControlledDocumentTypeCode::tryFrom($this->code);

        return $code !== null && in_array($code, ControlledDocumentTypeCode::issuableTypes(), true);
    }
}
