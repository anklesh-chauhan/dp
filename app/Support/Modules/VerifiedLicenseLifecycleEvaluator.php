<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Enums\ProductLicenseAuditEventType;
use App\Enums\ProductLicenseState;
use App\Models\ProductLicense;
use App\Support\Modules\Contracts\LicenseAuditRecorder;
use App\Support\Modules\Contracts\LicenseLifecycleEvaluator;
use App\Support\Modules\Contracts\ProductLicenseStateResolver;
use DateTimeInterface;

class VerifiedLicenseLifecycleEvaluator implements LicenseLifecycleEvaluator
{
    public function __construct(
        private readonly ProductLicenseStateResolver $stateResolver,
        private readonly LicenseAuditRecorder $auditRecorder,
    ) {}

    public function evaluate(
        ProductLicense $license,
        ?DateTimeInterface $at = null,
    ): ProductLicenseState {
        $state = $this->stateResolver->resolve($license, $at);

        if ($state === ProductLicenseState::Active) {
            return $state;
        }

        $eventType = match ($state) {
            ProductLicenseState::Grace => ProductLicenseAuditEventType::GraceStarted,
            ProductLicenseState::Expired => ProductLicenseAuditEventType::Expired,
            ProductLicenseState::Revoked => ProductLicenseAuditEventType::Revoked,
            ProductLicenseState::Invalid => ProductLicenseAuditEventType::VerificationFailed,
            ProductLicenseState::Active => ProductLicenseAuditEventType::Activated,
        };

        return $this->recordedState($license, $state, $eventType, $at);
    }

    private function recordedState(
        ProductLicense $license,
        ProductLicenseState $state,
        ProductLicenseAuditEventType $eventType,
        ?DateTimeInterface $occurredAt,
    ): ProductLicenseState {
        $this->auditRecorder->record($license, $eventType, $state, occurredAt: $occurredAt);

        return $state;
    }
}
