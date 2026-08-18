<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Enums\SecurityAuditEventType;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class SecurityAuditRecorder
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        SecurityAuditEventType $type,
        ?User $actor = null,
        ?User $subject = null,
        ?string $subjectEmail = null,
        ?string $reason = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): SecurityAuditEvent {
        return SecurityAuditEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => $type,
            'actor_id' => $actor?->getKey(),
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'subject_user_id' => $subject?->getKey(),
            'subject_email' => $subjectEmail ?? $subject?->email,
            'reason' => $reason,
            'context' => $context === [] ? null : $context,
            'ip_address' => $ipAddress ?? Request::ip(),
            'user_agent' => $userAgent ?? Request::userAgent(),
            'occurred_at' => now(),
        ]);
    }
}
