<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Models\EquipmentQualification;
use App\Filament\Resources\EquipmentQualifications\EquipmentQualificationResource;
use App\Filament\Resources\EquipmentQualifications\Pages\ListEquipmentQualifications;
use App\Filament\Resources\EquipmentQualifications\Pages\ViewEquipmentQualification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:EquipmentQualification',
        'View:EquipmentQualification',
        'Create:EquipmentQualification',
        'Update:EquipmentQualification',
        'Execute:EquipmentQualification',
        'Review:EquipmentQualification',
        'Approve:EquipmentQualification',
        'Manage:EquipmentQualification',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces QMS entitlement for the equipment qualification filament resource', function (): void {
    expect(EquipmentQualificationResource::canAccess())->toBeTrue()
        ->and(EquipmentQualificationResource::getNavigationGroup())->toBe('QMS')
        ->and(EquipmentQualificationResource::getNavigationSort())->toBe(17);

    config()->set('modules.enabled', ['dms']);

    expect(EquipmentQualificationResource::canAccess())->toBeFalse()
        ->and(EquipmentQualificationResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(EquipmentQualificationResource::getUrl())->assertForbidden();
});

it('denies direct Livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListEquipmentQualifications::class)->assertForbidden();
});

it('delegates equipment qualification lifecycle actions through the transition service', function (): void {
    $qualification = EquipmentQualification::factory()->create(['status' => EquipmentQualificationStatus::Draft]);

    Livewire::test(ViewEquipmentQualification::class, ['record' => $qualification->id])
        ->callAction('beginExecution', ['reason' => 'IQ execution started.'])
        ->assertNotified();

    expect($qualification->fresh()?->status)->toBe(EquipmentQualificationStatus::InProgress)
        ->and($qualification->auditEvents()->count())->toBe(1);
});
