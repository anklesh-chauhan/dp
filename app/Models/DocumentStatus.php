<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsLookupModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentStatus extends Model
{
    use IsLookupModel;

    public const DRAFT = 'draft';

    public const UNDER_REVIEW = 'under_review';

    public const APPROVED = 'approved';

    public const EFFECTIVE = 'effective';

    public const OBSOLETE = 'obsolete';

    public const ARCHIVED = 'archived';

    public const RETENTION_COMPLETED = 'retention_completed';

    public const DESTROYED = 'destroyed';

    public const REJECTED = 'rejected';

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

    protected $fillable = ['code', 'name', 'sort_order'];

    /**
     * @return HasMany<SopDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(SopDocument::class);
    }
}
