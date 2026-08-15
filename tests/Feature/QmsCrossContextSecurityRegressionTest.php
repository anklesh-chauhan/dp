<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\CapaType;
use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ComplaintType;
use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\AuditFindingEvent;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\CapaAuditEvent;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\ChangeControlAuditEvent;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\ComplaintAuditEvent;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\DeviationAuditEvent;
use App\Domain\QMS\Models\InternalAudit;
use App\Domain\QMS\Models\InternalAuditEvent;
use App\Domain\QMS\Models\Investigation;
use App\Domain\QMS\Models\InvestigationAuditEvent;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Models\ManagementReviewEvent;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\RiskAssessmentEvent;
use App\Domain\QMS\Models\SupplierQualification;
use App\Domain\QMS\Models\SupplierQualificationEvent;
use App\Domain\QMS\Services\AuditFindingCapaService;
use App\Domain\QMS\Services\AuditFindingTransitionService;
use App\Domain\QMS\Services\CapaTransitionService;
use App\Domain\QMS\Services\ChangeControlTransitionService;
use App\Domain\QMS\Services\ComplaintDeviationService;
use App\Domain\QMS\Services\ComplaintTransitionService;
use App\Domain\QMS\Services\DeviationTransitionService;
use App\Domain\QMS\Services\InternalAuditTransitionService;
use App\Domain\QMS\Services\InvestigationTransitionService;
use App\Domain\QMS\Services\ManagementReviewTransitionService;
use App\Domain\QMS\Services\QualityMetricsService;
use App\Domain\QMS\Services\RiskAssessmentTransitionService;
use App\Domain\QMS\Services\SupplierQualificationTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function qmsSecurityBoundaryOperations(User $actor): array
{
    $owner = User::factory()->create();
    $deviation = Deviation::factory()->create();
    $audit = InternalAudit::factory()->create();
    $finding = AuditFinding::factory()->create(['internal_audit_id' => $audit]);

    return [
        fn () => app(ChangeControlTransitionService::class)->transition(
            ChangeControl::factory()->create(), ChangeControlStatus::Submitted, $actor, 'Security matrix.',
        ),
        fn () => app(DeviationTransitionService::class)->transition(
            $deviation, DeviationStatus::Open, $actor, 'Security matrix.',
        ),
        fn () => app(InvestigationTransitionService::class)->transition(
            Investigation::factory()->create(['deviation_id' => $deviation]),
            InvestigationStatus::InProgress, $actor, 'Security matrix.',
        ),
        fn () => app(CapaTransitionService::class)->transition(
            Capa::factory()->create(['deviation_id' => $deviation]),
            CapaStatus::Planned, $actor, 'Security matrix.',
        ),
        fn () => app(ComplaintTransitionService::class)->transition(
            Complaint::factory()->create(), ComplaintStatus::Received, $actor, 'Security matrix.',
        ),
        fn () => app(InternalAuditTransitionService::class)->transition(
            $audit, InternalAuditStatus::Scheduled, $actor, 'Security matrix.',
        ),
        fn () => app(AuditFindingTransitionService::class)->transition(
            $finding, AuditFindingDisposition::ResponsePending, $actor, 'Security matrix.',
        ),
        fn () => app(RiskAssessmentTransitionService::class)->transition(
            RiskAssessment::factory()->create(), RiskAssessmentStatus::InReview, $actor, 'Security matrix.',
        ),
        fn () => app(SupplierQualificationTransitionService::class)->transition(
            SupplierQualification::factory()->create(),
            SupplierQualificationStatus::UnderAssessment, $actor, 'Security matrix.',
        ),
        fn () => app(ManagementReviewTransitionService::class)->transition(
            ManagementReview::factory()->create(),
            ManagementReviewStatus::Scheduled, $actor, 'Security matrix.',
        ),
        fn () => app(ComplaintDeviationService::class)->create(
            Complaint::factory()->create(['type' => ComplaintType::ProductQuality]),
            $actor, DeviationSeverity::Major, 'Security matrix.',
        ),
        fn () => app(AuditFindingCapaService::class)->create(
            $finding, $actor, $owner, CapaType::Corrective,
            'Security matrix action.', 'Security matrix.', today()->addMonth(),
        ),
        fn () => app(QualityMetricsService::class)->snapshot($actor),
    ];
}

it('fails every completed lifecycle handoff and metrics boundary closed when QMS is disabled', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    $operations = qmsSecurityBoundaryOperations(User::factory()->create());
    config()->set('modules.enabled', ['dms']);

    foreach ($operations as $operation) {
        expect($operation)->toThrow(ModuleNotEnabledException::class);
    }
});

it('denies every completed lifecycle handoff and metrics boundary without explicit permission', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach (qmsSecurityBoundaryOperations(User::factory()->create()) as $operation) {
        expect($operation)->toThrow(AuthorizationException::class);
    }
});

it('enforces append-only history and detects stored signature tampering across every QMS event stream', function (): void {
    $actor = User::factory()->create();
    $occurredAt = now()->startOfSecond();
    $reason = 'Cross-context signature regression.';
    $ipAddress = '203.0.113.101';
    $userAgent = 'QualiGxP-QMS-Security-Matrix/1.0';
    $hasher = app(ElectronicSignatureHasher::class);
    $verifier = app(ElectronicSignatureVerifier::class);
    $eventDefinitions = [
        [ChangeControlAuditEvent::class, 'to_status', ChangeControlStatus::Approved->value],
        [DeviationAuditEvent::class, 'to_status', DeviationStatus::Closed->value],
        [InvestigationAuditEvent::class, 'to_status', InvestigationStatus::Completed->value],
        [CapaAuditEvent::class, 'to_status', CapaStatus::Closed->value],
        [ComplaintAuditEvent::class, 'to_status', ComplaintStatus::Closed->value],
        [InternalAuditEvent::class, 'to_status', InternalAuditStatus::Closed->value],
        [AuditFindingEvent::class, 'to_disposition', AuditFindingDisposition::Closed->value],
        [RiskAssessmentEvent::class, 'to_status', RiskAssessmentStatus::Closed->value],
        [SupplierQualificationEvent::class, 'to_status', SupplierQualificationStatus::Qualified->value],
        [ManagementReviewEvent::class, 'to_status', ManagementReviewStatus::Completed->value],
    ];

    foreach ($eventDefinitions as [$eventClass, $meaningField, $meaning]) {
        $eventUuid = (string) str()->uuid();
        $signatureHash = $hasher->hashFor(
            recordKey: $eventUuid,
            meaning: $meaning,
            signerId: $actor->id,
            signedAt: $occurredAt,
            reason: $reason,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
        $event = $eventClass::factory()->create([
            'event_uuid' => $eventUuid,
            $meaningField => $meaning,
            'actor_id' => $actor->id,
            'reason' => $reason,
            'signature_hash' => $signatureHash,
            'signature_ip_address' => $ipAddress,
            'signature_user_agent' => $userAgent,
            'occurred_at' => $occurredAt,
        ]);

        expect($verifier->isValid($event))->toBeTrue();
        expect(fn () => $event->update(['reason' => 'tampered']))
            ->toThrow(LogicException::class);
        expect(fn () => $event->delete())
            ->toThrow(LogicException::class);

        DB::table($event->getTable())
            ->where('id', $event->getKey())
            ->update(['reason' => 'storage tampering']);

        expect($verifier->isValid($event->refresh()))->toBeFalse();
    }
});
