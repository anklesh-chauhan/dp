<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class RiskAssessmentLink extends MorphPivot
{
    protected $table = 'risk_assessment_links';

    public $incrementing = true;

    public $timestamps = true;

    protected $fillable = [
        'risk_assessment_id',
        'linkable_type',
        'linkable_id',
        'link_role',
    ];

    /** @return BelongsTo<RiskAssessment, $this> */
    public function riskAssessment(): BelongsTo
    {
        return $this->belongsTo(RiskAssessment::class);
    }

    /** @return MorphTo<Model, $this> */
    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
