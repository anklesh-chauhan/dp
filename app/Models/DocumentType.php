<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    public const SOP = 'SOP';

    public const LOG = 'LOG';

    public const BATCH_RECORD = 'BMR';

    public const FORM = 'FORM';

    protected $fillable = [
        'name',
        'code',
        'requires_sop_reference',
        'is_issuable',
    ];

    protected function casts(): array
    {
        return [
            'requires_sop_reference' => 'boolean',
            'is_issuable' => 'boolean',
        ];
    }

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
        return $this->requires_sop_reference;
    }

    public function isIssuableType(): bool
    {
        return $this->is_issuable;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return static::query()->orderBy('name')->pluck('name', 'id')->all();
    }
}
