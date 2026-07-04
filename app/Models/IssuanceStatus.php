<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\IsLookupModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IssuanceStatus extends Model
{
    use IsLookupModel;

    public const ACTIVE = 'active';

    public const RECALLED = 'recalled';

    public const DESTROYED = 'destroyed';

    protected $fillable = ['code', 'name', 'sort_order'];

    /**
     * @return HasMany<DocumentIssuance, $this>
     */
    public function issuances(): HasMany
    {
        return $this->hasMany(DocumentIssuance::class);
    }
}
