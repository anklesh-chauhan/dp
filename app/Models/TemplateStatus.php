<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsLookupModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateStatus extends Model
{
    use IsLookupModel;

    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    public const ARCHIVED = 'archived';

    protected $fillable = ['code', 'name', 'sort_order'];

    /**
     * @return HasMany<SopTemplate, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(SopTemplate::class);
    }

    /**
     * @return HasMany<SopTemplateVersion, $this>
     */
    public function templateVersions(): HasMany
    {
        return $this->hasMany(SopTemplateVersion::class);
    }
}
