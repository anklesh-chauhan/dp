<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use App\Enums\ProductLicenseAuditEventType;
use App\Enums\ProductLicenseState;
use App\Models\ProductLicense;
use App\Models\ProductLicenseAuditEvent;
use DateTimeInterface;

interface LicenseAuditRecorder
{
    /** @param array<string, mixed> $context */
    public function record(
        ProductLicense $license,
        ProductLicenseAuditEventType $eventType,
        ProductLicenseState $toState,
        array $context = [],
        ?DateTimeInterface $occurredAt = null,
    ): ?ProductLicenseAuditEvent;
}
