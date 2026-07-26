<?php

declare(strict_types=1);

namespace App\Support\Modules;

use App\Enums\ProductLicenseAuditEventType;
use App\Enums\ProductLicenseState;
use App\Models\ProductLicense;
use App\Models\ProductLicenseAuditEvent;
use App\Support\Modules\Contracts\LicenseAuditRecorder;
use DateTimeInterface;

class EloquentLicenseAuditRecorder implements LicenseAuditRecorder
{
    public function record(
        ProductLicense $license,
        ProductLicenseAuditEventType $eventType,
        ProductLicenseState $toState,
        array $context = [],
        ?DateTimeInterface $occurredAt = null,
    ): ?ProductLicenseAuditEvent {
        if (! $license->exists) {
            return null;
        }

        $previousEvent = $license->auditEvents()->latest('id')->first();

        if (
            $previousEvent?->to_state === $toState
            && $eventType !== ProductLicenseAuditEventType::Upgraded
        ) {
            return null;
        }

        return $license->auditEvents()->create([
            'event_type' => $eventType,
            'from_state' => $previousEvent?->to_state,
            'to_state' => $toState,
            'context' => $this->sanitize($context),
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /** @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitize(array $context): array
    {
        unset($context['payload'], $context['signature']);

        return $context;
    }
}
