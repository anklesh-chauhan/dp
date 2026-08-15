<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\CapaAuditEvent;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Services\CapaTransitionService;
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
        'Update:Capa',
        'Implement:Capa',
        'VerifyEffectiveness:Capa',
        'Close:Capa',
        'Manage:Capa',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->capa = Capa::factory()->create([
        'deviation_id' => Deviation::factory(),
        'action_plan' => 'Replace the component and revise preventive maintenance.',
    ]);
});

it('enforces the CAPA lifecycle and signs effectiveness and closure decisions', function (): void {
    $service = app(CapaTransitionService::class);
    $service->transition($this->capa, CapaStatus::Planned, $this->actor);
    $service->transition($this->capa, CapaStatus::InProgress, $this->actor);
    $service->transition($this->capa, CapaStatus::PendingEffectiveness, $this->actor, 'Implementation completed.');
    $service->transition(
        $this->capa,
        CapaStatus::Effective,
        $this->actor,
        'Effectiveness verified.',
        'No recurrence during the verification period.',
        ipAddress: '203.0.113.32',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );
    $closed = $service->transition($this->capa, CapaStatus::Closed, $this->actor, 'CAPA closed.');

    $events = $closed->auditEvents()->orderBy('id')->get();
    $effectivenessEvent = $events->firstWhere('to_status', CapaStatus::Effective);

    expect($closed->status)->toBe(CapaStatus::Closed)
        ->and($closed->completed_at)->not->toBeNull()
        ->and($closed->effectiveness_verified_at)->not->toBeNull()
        ->and($closed->closed_at)->not->toBeNull()
        ->and($closed->effectiveness_result)->toBe('No recurrence during the verification period.')
        ->and($events)->toHaveCount(5)
        ->and($effectivenessEvent)->toBeInstanceOf(CapaAuditEvent::class)
        ->and($effectivenessEvent?->signatureIpAddress())->toBe('203.0.113.32')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($effectivenessEvent))->toBeTrue();

    expect(fn () => $effectivenessEvent?->delete())->toThrow(LogicException::class);
});

it('requires implementation details and an effectiveness result', function (): void {
    $this->capa->update([
        'status' => CapaStatus::InProgress,
        'action_plan' => '',
    ]);

    expect(fn () => app(CapaTransitionService::class)->transition(
        $this->capa,
        CapaStatus::PendingEffectiveness,
        $this->actor,
    ))->toThrow(ValidationException::class);

    $this->capa->update([
        'status' => CapaStatus::PendingEffectiveness,
        'action_plan' => 'Implementation completed.',
    ]);

    expect(fn () => app(CapaTransitionService::class)->transition(
        $this->capa,
        CapaStatus::Effective,
        $this->actor,
    ))->toThrow(ValidationException::class)
        ->and(CapaAuditEvent::query()->count())->toBe(0);
});

it('supports ineffective rework and rejects unauthorized invalid and disabled transitions', function (): void {
    $this->capa->update(['status' => CapaStatus::PendingEffectiveness]);
    $service = app(CapaTransitionService::class);
    $service->transition(
        $this->capa,
        CapaStatus::Ineffective,
        $this->actor,
        'Recurrence detected.',
        'The corrective action did not prevent recurrence.',
    );
    $rework = $service->transition($this->capa, CapaStatus::InProgress, $this->actor);

    expect($rework->status)->toBe(CapaStatus::InProgress);

    expect(fn () => $service->transition(
        $rework,
        CapaStatus::Closed,
        $this->actor,
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        Capa::factory()->create(),
        CapaStatus::Planned,
        User::factory()->create(),
    ))->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        Capa::factory()->create(),
        CapaStatus::Planned,
        $this->actor,
    ))->toThrow(ModuleNotEnabledException::class);
});
