<?php

declare(strict_types=1);

namespace App\Domain\QMS\Enums;

enum InternalAuditType: string
{
    case Internal = 'internal';
    case Process = 'process';
    case System = 'system';
    case Product = 'product';
    case Supplier = 'supplier';
    case RegulatoryReadiness = 'regulatory_readiness';
}
