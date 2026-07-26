<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum SupplierQualificationStatus: string
{
    case Draft = 'draft';
    case UnderAssessment = 'under_assessment';
    case AuditRequired = 'audit_required';
    case Qualified = 'qualified';
    case ConditionallyQualified = 'conditionally_qualified';
    case Suspended = 'suspended';
    case Disqualified = 'disqualified';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
