<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Enums\ProductLicenseAuditEventType;
use App\Enums\ProductLicenseState;
use App\Models\ProductLicense;
use App\Support\Modules\Contracts\LicenseAuditRecorder;
use App\Support\Modules\Contracts\ProductLicenseRevoker;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class AuditedProductLicenseRevoker implements ProductLicenseRevoker
{
    public function __construct(
        private readonly LicenseAuditRecorder $auditRecorder,
    ) {}

    public function revoke(
        ProductLicense $license,
        ?DateTimeInterface $revokedAt = null,
    ): ProductLicense {
        return DB::transaction(function () use ($license, $revokedAt): ProductLicense {
            $effectiveAt = $revokedAt ?? now();

            $license->update(['revoked_at' => $effectiveAt]);
            $this->auditRecorder->record(
                $license,
                ProductLicenseAuditEventType::Revoked,
                ProductLicenseState::Revoked,
                occurredAt: $effectiveAt,
            );

            return $license->refresh();
        });
    }
}
