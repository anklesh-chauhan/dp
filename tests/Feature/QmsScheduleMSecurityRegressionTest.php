<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Enums\CsvValidationProjectStatus;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Enums\SiteMasterFileStatus;
use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Models\CompetencyCurriculum;
use App\Domain\QMS\Models\CsvRequirement;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\QMS\Models\EquipmentCalibration;
use App\Domain\QMS\Models\EquipmentCalibrationEvent;
use App\Domain\QMS\Models\EquipmentMaintenance;
use App\Domain\QMS\Models\EquipmentMaintenanceEvent;
use App\Domain\QMS\Models\EquipmentQualification;
use App\Domain\QMS\Models\EquipmentQualificationEvent;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Domain\QMS\Models\LaboratoryOosEventEvent;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Domain\QMS\Models\ProductQualityReviewEvent;
use App\Domain\QMS\Models\ProductRecall;
use App\Domain\QMS\Models\ProductRecallEvent;
use App\Domain\QMS\Models\ProductReturn;
use App\Domain\QMS\Models\ProductReturnEvent;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\ScheduleMGapAssessmentEvent;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Domain\QMS\Models\SiteMasterFileEvent;
use App\Domain\QMS\Models\ValidationMasterPlan;
use App\Domain\QMS\Models\ValidationMasterPlanEvent;
use App\Domain\QMS\Services\CompetencyService;
use App\Domain\QMS\Services\CsvRequirementApprovalService;
use App\Domain\QMS\Services\CsvValidationProjectService;
use App\Domain\QMS\Services\EquipmentCalibrationTransitionService;
use App\Domain\QMS\Services\EquipmentMaintenanceTransitionService;
use App\Domain\QMS\Services\EquipmentQualificationTransitionService;
use App\Domain\QMS\Services\InspectorEvidencePackService;
use App\Domain\QMS\Services\LaboratoryOosTransitionService;
use App\Domain\QMS\Services\ManagementReviewInputAssembler;
use App\Domain\QMS\Services\ProductQualityReviewTransitionService;
use App\Domain\QMS\Services\ProductRecallTransitionService;
use App\Domain\QMS\Services\ProductReturnTransitionService;
use App\Domain\QMS\Services\ScheduleMGapAssessmentTransitionService;
use App\Domain\QMS\Services\SiteMasterFileTransitionService;
use App\Domain\QMS\Services\ValidationMasterPlanTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * @return list<Closure(): mixed>
 */
function scheduleMSecurityBoundaryOperations(User $actor): array
{
    $csvProject = CsvValidationProject::factory()->create([
        'status' => CsvValidationProjectStatus::Draft,
    ]);
    $requirement = CsvRequirement::query()->create([
        'csv_validation_project_id' => $csvProject->id,
        'requirement_identifier' => 'URS-SEC-'.Str::upper(Str::random(4)),
        'version' => 1,
        'category' => 'Functional',
        'statement' => 'Security matrix requirement.',
        'acceptance_criteria' => 'Signed approval recorded.',
        'criticality' => CsvCriticality::High,
        'gxp_relevant' => true,
        'status' => CsvRequirementStatus::Draft,
    ]);
    $trainee = User::factory()->create();
    $curriculum = CompetencyCurriculum::factory()->create([
        'created_by' => $actor->id,
    ]);

    return [
        fn () => app(ProductQualityReviewTransitionService::class)->transition(
            ProductQualityReview::factory()->create(),
            ProductQualityReviewStatus::InProgress,
            $actor,
            'Security matrix.',
        ),
        fn () => app(ProductRecallTransitionService::class)->transition(
            ProductRecall::factory()->create(),
            ProductRecallStatus::Initiated,
            $actor,
            'Security matrix.',
        ),
        fn () => app(ProductReturnTransitionService::class)->transition(
            ProductReturn::factory()->create(),
            ProductReturnStatus::Received,
            $actor,
            'Security matrix.',
        ),
        fn () => app(LaboratoryOosTransitionService::class)->transition(
            LaboratoryOosEvent::factory()->create(),
            LaboratoryOosStatus::PhaseOne,
            $actor,
            'Security matrix.',
        ),
        fn () => app(ValidationMasterPlanTransitionService::class)->transition(
            ValidationMasterPlan::factory()->create(),
            ValidationMasterPlanStatus::Active,
            $actor,
            'Security matrix.',
        ),
        fn () => app(EquipmentQualificationTransitionService::class)->transition(
            EquipmentQualification::factory()->create(),
            EquipmentQualificationStatus::InProgress,
            $actor,
            'Security matrix.',
        ),
        fn () => app(EquipmentCalibrationTransitionService::class)->transition(
            EquipmentCalibration::factory()->create(),
            EquipmentCalibrationStatus::InProgress,
            $actor,
            'Security matrix.',
        ),
        fn () => app(EquipmentMaintenanceTransitionService::class)->transition(
            EquipmentMaintenance::factory()->create(),
            EquipmentMaintenanceStatus::InProgress,
            $actor,
            'Security matrix.',
        ),
        fn () => app(ScheduleMGapAssessmentTransitionService::class)->transition(
            ScheduleMGapAssessment::factory()->create(),
            ScheduleMGapAssessmentStatus::InProgress,
            $actor,
            'Security matrix.',
        ),
        fn () => app(SiteMasterFileTransitionService::class)->transition(
            SiteMasterFile::factory()->create(),
            SiteMasterFileStatus::InReview,
            $actor,
            'Security matrix.',
        ),
        fn () => app(CompetencyService::class)->assignCurriculum(
            $trainee,
            $curriculum,
            $actor,
        ),
        fn () => app(CsvRequirementApprovalService::class)->approve(
            $requirement,
            $actor,
            'Security matrix.',
        ),
        fn () => app(CsvValidationProjectService::class)->transition(
            $csvProject,
            CsvValidationProjectStatus::GxpAssessment,
            $actor,
            'Security matrix.',
        ),
        fn () => app(ManagementReviewInputAssembler::class)->assemble(
            ManagementReview::factory()->create(),
            $actor,
        ),
        fn () => app(InspectorEvidencePackService::class)->build(
            ScheduleMGapAssessment::factory()->create(),
            $actor,
        ),
    ];
}

it('fails every Schedule M lifecycle and export boundary closed when QMS is disabled', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    $operations = scheduleMSecurityBoundaryOperations(User::factory()->create());
    config()->set('modules.enabled', ['dms']);

    foreach ($operations as $operation) {
        expect($operation)->toThrow(ModuleNotEnabledException::class);
    }
});

it('denies every Schedule M lifecycle and export boundary without explicit permission', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach (scheduleMSecurityBoundaryOperations(User::factory()->create()) as $operation) {
        expect($operation)->toThrow(AuthorizationException::class);
    }
});

it('enforces append-only history and detects stored signature tampering across Schedule M event streams', function (): void {
    $actor = User::factory()->create();
    $occurredAt = now()->startOfSecond();
    $reason = 'Schedule M signature regression.';
    $ipAddress = '203.0.113.201';
    $userAgent = 'QualiGxP-QMS-ScheduleM-Security/1.0';
    $hasher = app(ElectronicSignatureHasher::class);
    $verifier = app(ElectronicSignatureVerifier::class);
    $eventDefinitions = [
        [ProductQualityReviewEvent::class, 'to_status', ProductQualityReviewStatus::Approved->value],
        [ProductRecallEvent::class, 'to_status', ProductRecallStatus::Closed->value],
        [ProductReturnEvent::class, 'to_status', ProductReturnStatus::Closed->value],
        [LaboratoryOosEventEvent::class, 'to_status', LaboratoryOosStatus::Closed->value],
        [ValidationMasterPlanEvent::class, 'to_status', ValidationMasterPlanStatus::Active->value],
        [EquipmentQualificationEvent::class, 'to_status', EquipmentQualificationStatus::Approved->value],
        [EquipmentCalibrationEvent::class, 'to_status', EquipmentCalibrationStatus::Completed->value],
        [EquipmentMaintenanceEvent::class, 'to_status', EquipmentMaintenanceStatus::Completed->value],
        [ScheduleMGapAssessmentEvent::class, 'to_status', ScheduleMGapAssessmentStatus::Approved->value],
        [SiteMasterFileEvent::class, 'to_status', SiteMasterFileStatus::Published->value],
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
