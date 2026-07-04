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

    public const REJECTED = 'rejected';

    protected $fillable = ['code', 'name', 'sort_order'];

    /**
     * @return HasMany<SopDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(SopDocument::class);
    }
}
