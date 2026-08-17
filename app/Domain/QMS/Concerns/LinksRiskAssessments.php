<?php

declare(strict_types=1);

namespace App\Domain\QMS\Concerns;

use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\RiskAssessmentLink;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait LinksRiskAssessments
{
    /** @return MorphToMany<RiskAssessment, $this, RiskAssessmentLink> */
    public function riskAssessments(): MorphToMany
    {
        return $this->morphToMany(
            RiskAssessment::class,
            'linkable',
            'risk_assessment_links',
        )
            ->using(RiskAssessmentLink::class)
            ->withPivot(['id', 'link_role'])
            ->withTimestamps();
    }
}
