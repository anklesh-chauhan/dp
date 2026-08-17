<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentAssetStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Filament\Resources\EquipmentAssets\EquipmentAssetResource;
use App\Filament\Resources\EquipmentAssets\Pages\ListEquipmentAssets;
use App\Filament\Resources\EquipmentAssets\Pages\ViewEquipmentAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:EquipmentAsset',
        'View:EquipmentAsset',
        'Create:EquipmentAsset',
        'Update:EquipmentAsset',
        'Manage:EquipmentAsset',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces QMS entitlement for the equipment asset filament resource', function (): void {
    expect(EquipmentAssetResource::canAccess())->toBeTrue()
        ->and(EquipmentAssetResource::getNavigationGroup())->toBe('QMS')
        ->and(EquipmentAssetResource::getNavigationSort())->toBe(16);

    config()->set('modules.enabled', ['dms']);

    expect(EquipmentAssetResource::canAccess())->toBeFalse()
        ->and(EquipmentAssetResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(EquipmentAssetResource::getUrl())->assertForbidden();
});

it('denies direct Livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListEquipmentAssets::class)->assertForbidden();
});

it('delegates equipment asset lifecycle actions through the transition service', function (): void {
    $asset = EquipmentAsset::factory()->create(['status' => EquipmentAssetStatus::Active]);

    Livewire::test(ViewEquipmentAsset::class, ['record' => $asset->id])
        ->callAction('deactivate', ['reason' => 'Temporary shutdown.'])
        ->assertNotified();

    expect($asset->fresh()?->status)->toBe(EquipmentAssetStatus::Inactive)
        ->and($asset->auditEvents()->count())->toBe(1);
});
