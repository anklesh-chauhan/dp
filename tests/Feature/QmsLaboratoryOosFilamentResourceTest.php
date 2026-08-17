<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\LaboratoryOosPhaseOutcome;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Filament\Resources\LaboratoryOosEvents\LaboratoryOosEventResource;
use App\Filament\Resources\LaboratoryOosEvents\Pages\ListLaboratoryOosEvents;
use App\Filament\Resources\LaboratoryOosEvents\Pages\ViewLaboratoryOosEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:LaboratoryOosEvent',
        'View:LaboratoryOosEvent',
        'Create:LaboratoryOosEvent',
        'Update:LaboratoryOosEvent',
        'Investigate:LaboratoryOosEvent',
        'Confirm:LaboratoryOosEvent',
        'Close:LaboratoryOosEvent',
        'Manage:LaboratoryOosEvent',
        'Create:Deviation',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces QMS entitlement for the laboratory oos filament resource', function (): void {
    expect(LaboratoryOosEventResource::canAccess())->toBeTrue()
        ->and(LaboratoryOosEventResource::getNavigationGroup())->toBe('QMS')
        ->and(LaboratoryOosEventResource::getNavigationSort())->toBe(14);

    config()->set('modules.enabled', ['dms']);

    expect(LaboratoryOosEventResource::canAccess())->toBeFalse()
        ->and(LaboratoryOosEventResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(LaboratoryOosEventResource::getUrl())->assertForbidden();
});

it('denies direct Livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListLaboratoryOosEvents::class)->assertForbidden();
});

it('delegates laboratory oos lifecycle actions through the transition service', function (): void {
    $event = LaboratoryOosEvent::factory()->create(['status' => LaboratoryOosStatus::Draft]);

    Livewire::test(ViewLaboratoryOosEvent::class, ['record' => $event->id])
        ->callAction('beginPhaseOne', ['reason' => 'Phase I laboratory investigation started.'])
        ->assertNotified();

    expect($event->fresh()?->status)->toBe(LaboratoryOosStatus::PhaseOne)
        ->and($event->auditEvents()->count())->toBe(1);

    Livewire::test(ViewLaboratoryOosEvent::class, ['record' => $event->id])
        ->callAction('confirm', [
            'phase_one_outcome' => LaboratoryOosPhaseOutcome::ConfirmedOos->value,
            'phase_one_notes' => 'No assignable laboratory error.',
            'reason' => 'Confirmed OOS after Phase I.',
        ])
        ->assertNotified();

    expect($event->fresh()?->status)->toBe(LaboratoryOosStatus::Confirmed)
        ->and($event->fresh()?->phase_one_outcome)->toBe(LaboratoryOosPhaseOutcome::ConfirmedOos)
        ->and($event->auditEvents()->count())->toBe(2);
});

it('opens a deviation from a confirmed laboratory oos event through the Filament handoff action', function (): void {
    $event = LaboratoryOosEvent::factory()->create([
        'status' => LaboratoryOosStatus::Confirmed,
    ]);

    Livewire::test(ViewLaboratoryOosEvent::class, ['record' => $event->id])
        ->callAction('openDeviation', [
            'severity' => DeviationSeverity::Major->value,
            'immediate_actions' => 'Quarantined affected lots.',
            'investigation_due_at' => today()->addDays(10)->format('Y-m-d'),
            'reason' => 'Confirmed OOS requires deviation investigation.',
        ])
        ->assertNotified();

    expect(Deviation::query()->count())->toBe(1)
        ->and($event->fresh()?->deviation_id)->not->toBeNull();
});
