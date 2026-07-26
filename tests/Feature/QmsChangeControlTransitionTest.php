<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\ChangeControlAuditEvent;
use App\Domain\QMS\Services\ChangeControlTransitionService;
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
        'Submit:ChangeControl',
        'Review:ChangeControl',
        'Approve:ChangeControl',
        'Implement:ChangeControl',
        'VerifyEffectiveness:ChangeControl',
        'Close:ChangeControl',
        'Manage:ChangeControl',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->changeControl = ChangeControl::factory()->create();
});

it('enforces the lifecycle and records attributable append-only transitions', function (): void {
    $service = app(ChangeControlTransitionService::class);

    $service->transition(
        $this->changeControl,
        ChangeControlStatus::Submitted,
        $this->actor,
        'Ready for quality review.',
        [
            'source' => 'controlled-test',
            'payload' => 'must-not-persist',
            'signature' => 'must-not-persist',
        ],
    );
    $service->transition($this->changeControl, ChangeControlStatus::UnderReview, $this->actor);
    $approved = $service->transition(
        $this->changeControl,
        ChangeControlStatus::Approved,
        $this->actor,
        'Benefits outweigh assessed risks.',
    );

    $events = $approved->auditEvents()->orderBy('id')->get();

    expect($approved->status)->toBe(ChangeControlStatus::Approved)
        ->and($approved->submitted_at)->not->toBeNull()
        ->and($approved->approved_at)->not->toBeNull()
        ->and($events)->toHaveCount(3)
        ->and($events->pluck('from_status')->all())->toBe([
            ChangeControlStatus::Draft,
            ChangeControlStatus::Submitted,
            ChangeControlStatus::UnderReview,
        ])
        ->and($events->pluck('to_status')->all())->toBe([
            ChangeControlStatus::Submitted,
            ChangeControlStatus::UnderReview,
            ChangeControlStatus::Approved,
        ])
        ->and($events->first()->actor?->is($this->actor))->toBeTrue()
        ->and($events->first()->context)->toBe(['source' => 'controlled-test']);

    expect(fn () => $events->first()->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class)
        ->and(fn () => $events->first()->delete())
        ->toThrow(LogicException::class);
});

it('rejects unauthorized and invalid transitions without audit events', function (): void {
    $unauthorizedUser = User::factory()->create();

    expect(fn () => app(ChangeControlTransitionService::class)->transition(
        $this->changeControl,
        ChangeControlStatus::Submitted,
        $unauthorizedUser,
    ))->toThrow(AuthorizationException::class);

    expect(fn () => app(ChangeControlTransitionService::class)->transition(
        $this->changeControl,
        ChangeControlStatus::Approved,
        $this->actor,
    ))->toThrow(ValidationException::class)
        ->and($this->changeControl->fresh()?->status)->toBe(ChangeControlStatus::Draft)
        ->and(ChangeControlAuditEvent::query()->count())->toBe(0);
});

it('requires the QMS entitlement for lifecycle transitions', function (): void {
    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(ChangeControlTransitionService::class)->transition(
        $this->changeControl,
        ChangeControlStatus::Submitted,
        $this->actor,
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(ChangeControlAuditEvent::query()->count())->toBe(0);
});
