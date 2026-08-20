<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\LaboratoryOosPhaseOutcome;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Domain\QMS\Models\LaboratoryOosEventEvent;
use App\Domain\QMS\Services\LaboratoryOosTransitionService;
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
        'Investigate:LaboratoryOosEvent',
        'Confirm:LaboratoryOosEvent',
        'Close:LaboratoryOosEvent',
        'Manage:LaboratoryOosEvent',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->event = LaboratoryOosEvent::factory()->create();
});

it('records an attributable timeline and signs consequential laboratory oos decisions', function (): void {
    $service = app(LaboratoryOosTransitionService::class);
    $service->transition($this->event, LaboratoryOosStatus::PhaseOne, $this->actor, 'Phase I opened.');
    $service->transition(
        $this->event,
        LaboratoryOosStatus::PhaseTwo,
        $this->actor,
        'Assignable laboratory error ruled out.',
        phaseOneOutcome: LaboratoryOosPhaseOutcome::ConfirmedOos,
        phaseOneNotes: 'No assignable error identified.',
    );
    $confirmed = $service->transition(
        $this->event,
        LaboratoryOosStatus::Confirmed,
        $this->actor,
        'Confirmed true OOS after Phase II.',
        ['signature' => 'must-not-be-recorded', 'channel' => 'lab'],
        ipAddress: '203.0.113.41',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );

    $deviation = Deviation::factory()->create();
    $confirmed->update(['deviation_id' => $deviation->id]);

    $closed = $service->transition(
        $this->event,
        LaboratoryOosStatus::Closed,
        $this->actor,
        'Linked deviation opened and event closed.',
    );

    $events = $closed->auditEvents()->orderBy('id')->get();
    $signedEvent = $events->firstWhere('to_status', LaboratoryOosStatus::Confirmed);

    expect($closed->status)->toBe(LaboratoryOosStatus::Closed)
        ->and($closed->started_at)->not->toBeNull()
        ->and($closed->phase_one_completed_at)->not->toBeNull()
        ->and($closed->phase_two_completed_at)->not->toBeNull()
        ->and($closed->closed_at)->not->toBeNull()
        ->and($closed->phase_one_outcome)->toBe(LaboratoryOosPhaseOutcome::ConfirmedOos)
        ->and($events)->toHaveCount(4)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($signedEvent?->signatureMeaning())->toBe(LaboratoryOosStatus::Confirmed->value)
        ->and($signedEvent?->signatureSignerId())->toBe($this->actor->id)
        ->and($signedEvent?->signatureIpAddress())->toBe('203.0.113.41')
        ->and($signedEvent?->context)->toMatchArray(['channel' => 'lab'])
        ->and($signedEvent?->signatureContentDigest())->not->toBeNull()
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signedEvent))->toBeTrue();

    expect(fn () => $signedEvent->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('requires phase one outcome and linked investigation or deviation before gated transitions', function (): void {
    $this->event->update(['status' => LaboratoryOosStatus::PhaseOne]);
    $service = app(LaboratoryOosTransitionService::class);

    expect(fn () => $service->transition(
        $this->event,
        LaboratoryOosStatus::PhaseTwo,
        $this->actor,
        'Leaving Phase I without outcome.',
    ))->toThrow(ValidationException::class);

    $this->event->update([
        'status' => LaboratoryOosStatus::Confirmed,
        'phase_one_outcome' => LaboratoryOosPhaseOutcome::ConfirmedOos,
    ]);

    expect(fn () => $service->transition(
        $this->event,
        LaboratoryOosStatus::Closed,
        $this->actor,
        'Closing without investigation or deviation.',
    ))->toThrow(ValidationException::class)
        ->and(LaboratoryOosEventEvent::query()->count())->toBe(0);
});

it('rejects missing reasons unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(LaboratoryOosTransitionService::class);

    expect(fn () => $service->transition(
        $this->event,
        LaboratoryOosStatus::PhaseOne,
        $this->actor,
        ' ',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        $this->event,
        LaboratoryOosStatus::PhaseOne,
        User::factory()->create(),
        'Begin Phase I.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->transition(
        $this->event,
        LaboratoryOosStatus::Closed,
        $this->actor,
        'Invalid direct closure.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->event,
        LaboratoryOosStatus::PhaseOne,
        $this->actor,
        'Begin Phase I.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(LaboratoryOosEventEvent::query()->count())->toBe(0);
});
