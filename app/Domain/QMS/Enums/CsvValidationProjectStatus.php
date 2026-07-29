<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum CsvValidationProjectStatus: string
{
    case Draft = 'draft';
    case GxpAssessment = 'gxp_assessment';
    case Planning = 'planning';
    case Specification = 'specification';
    case Testing = 'testing';
    case DeviationResolution = 'deviation_resolution';
    case ValidationReview = 'validation_review';
    case Released = 'released';
    case PeriodicReview = 'periodic_review';
    case Retired = 'retired';
    case Cancelled = 'cancelled';
}
