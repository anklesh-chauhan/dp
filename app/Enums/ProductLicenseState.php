<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductLicenseState: string
{
    case Active = 'active';
    case Grace = 'grace';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Invalid = 'invalid';
}
