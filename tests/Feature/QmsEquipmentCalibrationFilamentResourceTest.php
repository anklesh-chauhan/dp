<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Models\EquipmentCalibration;
use App\Filament\Resources\EquipmentCalibrations\EquipmentCalibrationResource;
use App\Filament\Resources\EquipmentCalibrations\Pages\ListEquipmentCalibrations;
use App\Filament\Resources\EquipmentCalibrations\Pages\ViewEquipmentCalibration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:EquipmentCalibration',
        'View:EquipmentCalibration',
        'Create:EquipmentCalibration',
        'Update:EquipmentCalibration',
        'Perform:EquipmentCalibration',
        'Verify:EquipmentCalibration',
        'Manage:EquipmentCalibration',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces QMS entitlement for the equipment calibration filament resource', function (): void {
    expect(EquipmentCalibrationResource::canAccess())->toBeTrue()
        ->and(EquipmentCalibrationResource::getNavigationGroup())->toBe('QMS')
        ->and(EquipmentCalibrationResource::getNavigationSort())->toBe(22);

    config()->set('modules.enabled', ['dms']);

    expect(EquipmentCalibrationResource::canAccess())->toBeFalse()
        ->and(EquipmentCalibrationResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(EquipmentCalibrationResource::getUrl())->assertForbidden();
});

it('denies direct Livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListEquipmentCalibrations::class)->assertForbidden();
});

it('delegates equipment calibration lifecycle actions through the transition service', function (): void {
    $calibration = EquipmentCalibration::factory()->create(['status' => EquipmentCalibrationStatus::Scheduled]);

    Livewire::test(ViewEquipmentCalibration::class, ['record' => $calibration->id])
        ->callAction('beginPerformance', ['reason' => 'Calibration performance started.'])
        ->assertNotified();

    expect($calibration->fresh()?->status)->toBe(EquipmentCalibrationStatus::InProgress)
        ->and($calibration->auditEvents()->count())->toBe(1);
});
