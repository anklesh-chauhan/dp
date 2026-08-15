<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\DeviationAuditEvent;
use App\Domain\QMS\Models\Investigation;
use App\Domain\QMS\Services\DeviationTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    $this->permissions = [
        'Submit:Deviation',
        'Investigate:Deviation',
        'VerifyEffectiveness:Deviation',
        'Close:Deviation',
        'Manage:Deviation',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->deviation = Deviation::factory()->create();
});

it('records the attributable lifecycle and signs consequential transitions', function (): void {
    $service = app(DeviationTransitionService::class);
    $service->transition($this->deviation, DeviationStatus::Open, $this->actor);
    $service->transition($this->deviation, DeviationStatus::UnderInvestigation, $this->actor);
    Investigation::factory()->create([
        'deviation_id' => $this->deviation,
        'status' => InvestigationStatus::Completed,
        'root_cause' => 'Root cause established.',
        'conclusion' => 'Investigation approved.',
        'completed_at' => now(),
    ]);
    $completed = $service->transition(
        $this->deviation,
        DeviationStatus::InvestigationComplete,
        $this->actor,
        'Root cause analysis completed.',
        ipAddress: '203.0.113.30',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );

    $events = $completed->auditEvents()->orderBy('id')->get();
    $signedEvent = $events->last();

    expect($completed->status)->toBe(DeviationStatus::InvestigationComplete)
        ->and($events)->toHaveCount(3)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($signedEvent->signatureMeaning())->toBe(DeviationStatus::InvestigationComplete->value)
        ->and($signedEvent->signatureSignerId())->toBe($this->actor->id)
        ->and($signedEvent->signatureIpAddress())->toBe('203.0.113.30')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signedEvent))->toBeTrue();

    expect(fn () => $signedEvent->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('prevents a deviation from leaving investigation until every investigation is completed', function (): void {
    $this->deviation->update(['status' => DeviationStatus::UnderInvestigation]);
    Investigation::factory()->create([
        'deviation_id' => $this->deviation,
        'status' => InvestigationStatus::InProgress,
    ]);

    expect(fn () => app(DeviationTransitionService::class)->transition(
        $this->deviation,
        DeviationStatus::InvestigationComplete,
        $this->actor,
    ))->toThrow(ValidationException::class)
        ->and($this->deviation->fresh()?->status)->toBe(DeviationStatus::UnderInvestigation)
        ->and(DeviationAuditEvent::query()->count())->toBe(0);
});

it('prevents effectiveness review until every linked CAPA is effective', function (): void {
    $this->deviation->update(['status' => DeviationStatus::CapaRequired]);
    Capa::factory()->create([
        'deviation_id' => $this->deviation,
        'status' => CapaStatus::PendingEffectiveness,
    ]);

    expect(fn () => app(DeviationTransitionService::class)->transition(
        $this->deviation,
        DeviationStatus::EffectivenessReview,
        $this->actor,
    ))->toThrow(ValidationException::class)
        ->and($this->deviation->fresh()?->status)->toBe(DeviationStatus::CapaRequired)
        ->and(DeviationAuditEvent::query()->count())->toBe(0);
});

it('rejects unauthorized invalid and disabled transitions without events', function (): void {
    expect(fn () => app(DeviationTransitionService::class)->transition(
        $this->deviation,
        DeviationStatus::Open,
        User::factory()->create(),
    ))->toThrow(AuthorizationException::class);

    expect(fn () => app(DeviationTransitionService::class)->transition(
        $this->deviation,
        DeviationStatus::Closed,
        $this->actor,
    ))->toThrow(ValidationException::class)
        ->and(DeviationAuditEvent::query()->count())->toBe(0);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(DeviationTransitionService::class)->transition(
        $this->deviation,
        DeviationStatus::Open,
        $this->actor,
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(DeviationAuditEvent::query()->count())->toBe(0);
});
