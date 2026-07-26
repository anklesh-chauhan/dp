<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductLicenseAuditEventType: string
{
    case Activated = 'activated';
    case Upgraded = 'upgraded';
    case VerificationFailed = 'verification_failed';
    case GraceStarted = 'grace_started';
    case Expired = 'expired';
    case Revoked = 'revoked';
}
