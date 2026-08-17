<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Domain\QMS\Services\LaboratoryOosDeviationService;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach (['Investigate:LaboratoryOosEvent', 'Create:Deviation'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo(['Investigate:LaboratoryOosEvent', 'Create:Deviation']);
    $this->event = LaboratoryOosEvent::factory()->create([
        'status' => LaboratoryOosStatus::Confirmed,
        'title' => 'Assay OOS for Lot A',
        'test_name' => 'Assay HPLC',
    ]);
});

it('atomically opens one traceable deviation from a confirmed laboratory oos event', function (): void {
    $service = app(LaboratoryOosDeviationService::class);
    $deviation = $service->create(
        $this->event,
        $this->actor,
        DeviationSeverity::Critical,
        'Confirmed OOS requires deviation investigation.',
        'Affected stock quarantined.',
        today()->addDays(14),
    );
    $retry = $service->create(
        $this->event,
        $this->actor,
        DeviationSeverity::Minor,
        'Safe retry.',
    );

    expect($retry->is($deviation))->toBeTrue()
        ->and(Deviation::query()->count())->toBe(1)
        ->and($this->event->fresh()?->deviation_id)->toBe($deviation->id)
        ->and($this->event->fresh()?->auditEvents)->toHaveCount(1)
        ->and($this->event->fresh()?->auditEvents->first()?->context)
        ->toBe(['deviation_id' => $deviation->id])
        ->and($deviation->severity)->toBe(DeviationSeverity::Critical)
        ->and($deviation->immediate_actions)->toBe('Affected stock quarantined.')
        ->and($deviation->investigation_due_at?->toDateString())->toBe(today()->addDays(14)->toDateString());

    expect(fn () => $this->event->fresh()?->update(['deviation_id' => null]))
        ->toThrow(LogicException::class);
});

it('rejects ineligible events unauthorized actors and disabled QMS', function (): void {
    $service = app(LaboratoryOosDeviationService::class);
    $ineligible = LaboratoryOosEvent::factory()->create([
        'status' => LaboratoryOosStatus::PhaseOne,
    ]);

    expect(fn () => $service->create(
        $ineligible,
        $this->actor,
        DeviationSeverity::Major,
        'Not confirmed.',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->create(
        $this->event,
        User::factory()->create(),
        DeviationSeverity::Major,
        'Unauthorized.',
    ))->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->create(
        $this->event,
        $this->actor,
        DeviationSeverity::Major,
        'Disabled module.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(Deviation::query()->count())->toBe(0);
});
