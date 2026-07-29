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

    public const OBSOLETE = 'obsolete';

    public const RETENTION_COMPLETED = 'retention_completed';

    public const DESTROYED = 'destroyed';

    protected $fillable = ['code', 'name', 'sort_order'];

    /**
     * @return list<string>
     */
    public static function retentionLifecycleCodes(): array
    {
        return [
            self::OBSOLETE,
            self::ARCHIVED,
            self::RETENTION_COMPLETED,
            self::DESTROYED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function archivedOrBeyondCodes(): array
    {
        return [
            self::ARCHIVED,
            self::RETENTION_COMPLETED,
            self::DESTROYED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function editableCodes(): array
    {
        return [
            self::DRAFT,
            self::PUBLISHED,
        ];
    }

    /**
     * @return HasMany<DocumentTemplate, $this>
     */
    public function templates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class);
    }

    /**
     * @return HasMany<DocumentTemplateVersion, $this>
     */
    public function templateVersions(): HasMany
    {
        return $this->hasMany(DocumentTemplateVersion::class);
    }
}
