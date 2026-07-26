<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\CapaType;
use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\Investigation;
use App\Filament\Resources\Capas\CapaResource;
use App\Filament\Resources\Capas\Pages\CreateCapa;
use App\Filament\Resources\Capas\Pages\ListCapas;
use App\Filament\Resources\Capas\Pages\ViewCapa;
use App\Filament\Resources\Investigations\InvestigationResource;
use App\Filament\Resources\Investigations\Pages\CreateInvestigation;
use App\Filament\Resources\Investigations\Pages\ListInvestigations;
use App\Filament\Resources\Investigations\Pages\ViewInvestigation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    $this->permissions = [
        'ViewAny:Investigation',
        'View:Investigation',
        'Create:Investigation',
        'Update:Investigation',
        'Review:Investigation',
        'Complete:Investigation',
        'ViewAny:Capa',
        'View:Capa',
        'Create:Capa',
        'Update:Capa',
        'Implement:Capa',
        'VerifyEffectiveness:Capa',
        'Close:Capa',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces entitlement and direct-access permissions for both linked resources', function (): void {
    expect(InvestigationResource::canAccess())->toBeTrue()
        ->and(CapaResource::canAccess())->toBeTrue()
        ->and(InvestigationResource::getNavigationGroup())->toBe('QMS · Quality Events')
        ->and(CapaResource::getNavigationGroup())->toBe('QMS · Quality Events');

    config()->set('modules.enabled', ['dms']);

    expect(InvestigationResource::canAccess())->toBeFalse()
        ->and(CapaResource::canAccess())->toBeFalse();

    $this->get(InvestigationResource::getUrl())->assertForbidden();
    $this->get(CapaResource::getUrl())->assertForbidden();

    config()->set('modules.enabled', ['dms', 'qms']);
    $this->actingAs(User::factory()->create());

    Livewire::test(ListInvestigations::class)->assertForbidden();
    Livewire::test(ListCapas::class)->assertForbidden();
});

it('creates linked investigation and CAPA drafts', function (): void {
    $deviation = Deviation::factory()->create();

    Livewire::test(CreateInvestigation::class)
        ->fillForm([
            'deviation_id' => $deviation->id,
            'lead_id' => $this->user->id,
            'due_at' => today()->addDays(15)->format('Y-m-d'),
            'methodology' => 'Structured 5 Whys investigation.',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $investigation = Investigation::query()->sole();

    Livewire::test(CreateCapa::class)
        ->fillForm([
            'deviation_id' => $deviation->id,
            'investigation_id' => $investigation->id,
            'type' => CapaType::CorrectiveAndPreventive->value,
            'title' => 'Revise maintenance controls',
            'action_plan' => 'Replace the component and revise the maintenance frequency.',
            'owner_id' => $this->user->id,
            'due_at' => today()->addDays(30)->format('Y-m-d'),
            'effectiveness_due_at' => today()->addDays(60)->format('Y-m-d'),
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($investigation->status)->toBe(InvestigationStatus::Draft)
        ->and($investigation->investigation_number)->toStartWith('INV-')
        ->and(Capa::query()->sole()->capa_number)->toStartWith('CAPA-')
        ->and(Capa::query()->sole()->investigation_id)->toBe($investigation->id);
});

it('delegates investigation and CAPA decisions to their signed lifecycle services', function (): void {
    $investigation = Investigation::factory()->create();

    Livewire::test(ViewInvestigation::class, ['record' => $investigation->id])
        ->callAction('begin', ['reason' => 'Investigation work started.'])
        ->assertNotified();

    expect($investigation->fresh()?->status)->toBe(InvestigationStatus::InProgress)
        ->and($investigation->auditEvents()->count())->toBe(1);

    $capa = Capa::factory()->create(['status' => CapaStatus::PendingEffectiveness]);

    Livewire::test(ViewCapa::class, ['record' => $capa->id])
        ->callAction('markEffective', [
            'reason' => 'Verification period completed.',
            'effectiveness_result' => 'No recurrence was observed.',
        ])
        ->assertNotified();

    $event = $capa->auditEvents()->sole();

    expect($capa->fresh()?->status)->toBe(CapaStatus::Effective)
        ->and($capa->fresh()?->effectiveness_result)->toBe('No recurrence was observed.')
        ->and($event->signature_hash)->not->toBeNull();
});
