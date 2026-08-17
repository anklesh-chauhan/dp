<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Models\EquipmentMaintenance;
use App\Filament\Resources\EquipmentMaintenances\EquipmentMaintenanceResource;
use App\Filament\Resources\EquipmentMaintenances\Pages\ListEquipmentMaintenances;
use App\Filament\Resources\EquipmentMaintenances\Pages\ViewEquipmentMaintenance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:EquipmentMaintenance',
        'View:EquipmentMaintenance',
        'Create:EquipmentMaintenance',
        'Update:EquipmentMaintenance',
        'Perform:EquipmentMaintenance',
        'Manage:EquipmentMaintenance',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces QMS entitlement for the equipment maintenance filament resource', function (): void {
    expect(EquipmentMaintenanceResource::canAccess())->toBeTrue()
        ->and(EquipmentMaintenanceResource::getNavigationGroup())->toBe('QMS')
        ->and(EquipmentMaintenanceResource::getNavigationSort())->toBe(23);

    config()->set('modules.enabled', ['dms']);

    expect(EquipmentMaintenanceResource::canAccess())->toBeFalse()
        ->and(EquipmentMaintenanceResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(EquipmentMaintenanceResource::getUrl())->assertForbidden();
});

it('denies direct Livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListEquipmentMaintenances::class)->assertForbidden();
});

it('delegates equipment maintenance lifecycle actions through the transition service', function (): void {
    $maintenance = EquipmentMaintenance::factory()->create(['status' => EquipmentMaintenanceStatus::Planned]);

    Livewire::test(ViewEquipmentMaintenance::class, ['record' => $maintenance->id])
        ->callAction('beginWork', ['reason' => 'PM work started.'])
        ->assertNotified();

    expect($maintenance->fresh()?->status)->toBe(EquipmentMaintenanceStatus::InProgress)
        ->and($maintenance->auditEvents()->count())->toBe(1);
});
