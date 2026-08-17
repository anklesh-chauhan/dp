<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Models\ValidationMasterPlan;
use App\Domain\QMS\Models\ValidationMasterPlanEvent;
use App\Domain\QMS\Services\ValidationMasterPlanTransitionService;
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
        'Approve:ValidationMasterPlan',
        'Update:ValidationMasterPlan',
        'Retire:ValidationMasterPlan',
        'Manage:ValidationMasterPlan',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->plan = ValidationMasterPlan::factory()->create();
});

it('records an attributable timeline and signs activate retire and cancel decisions', function (): void {
    $service = app(ValidationMasterPlanTransitionService::class);
    $activated = $service->transition(
        $this->plan,
        ValidationMasterPlanStatus::Active,
        $this->actor,
        'VMP approved for the current period.',
        ['signature' => 'must-not-be-recorded'],
        '203.0.113.70',
        'QualiGxP-QMS-Test/1.0',
    );
    $service->transition($this->plan, ValidationMasterPlanStatus::UnderRevision, $this->actor, 'Periodic revision started.');
    $retired = $service->transition($this->plan, ValidationMasterPlanStatus::Retired, $this->actor, 'VMP retired after replacement.');

    $events = $retired->auditEvents()->orderBy('id')->get();
    $signedActivate = $events->first(fn (ValidationMasterPlanEvent $event): bool => $event->to_status === ValidationMasterPlanStatus::Active);

    expect($activated->status)->toBe(ValidationMasterPlanStatus::Active)
        ->and($activated->approved_by)->toBe($this->actor->getKey())
        ->and($activated->approved_at)->not->toBeNull()
        ->and($retired->status)->toBe(ValidationMasterPlanStatus::Retired)
        ->and($retired->retired_at)->not->toBeNull()
        ->and($events)->toHaveCount(3)
        ->and($signedActivate?->signature_hash)->not->toBeNull()
        ->and($signedActivate?->signatureIpAddress())->toBe('203.0.113.70')
        ->and($signedActivate?->context)->toBe([])
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signedActivate))->toBeTrue();

    expect(fn () => $signedActivate->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('rejects missing reasons unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(ValidationMasterPlanTransitionService::class);

    expect(fn () => $service->transition(
        $this->plan,
        ValidationMasterPlanStatus::Active,
        $this->actor,
        ' ',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        $this->plan,
        ValidationMasterPlanStatus::Active,
        User::factory()->create(),
        'Activate VMP.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->transition(
        $this->plan,
        ValidationMasterPlanStatus::Retired,
        $this->actor,
        'Invalid direct retirement.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->plan,
        ValidationMasterPlanStatus::Active,
        $this->actor,
        'Activate VMP.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(ValidationMasterPlanEvent::query()->count())->toBe(0);
});
