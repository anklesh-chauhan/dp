<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentAssetCategory;
use App\Domain\QMS\Enums\EquipmentAssetCriticality;
use App\Domain\QMS\Enums\EquipmentAssetStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the equipment asset schema', function (): void {
    expect(Schema::hasColumns('equipment_assets', [
        'asset_number',
        'name',
        'asset_tag',
        'location',
        'category',
        'criticality',
        'gxp_impact',
        'status',
        'department_id',
        'owner_id',
        'validation_master_plan_id',
        'manufacturer',
        'model',
        'serial_number',
        'installed_at',
        'commissioned_at',
    ]))->toBeTrue()
        ->and(Schema::hasTable('equipment_asset_events'))->toBeTrue();
});

it('persists equipment asset fields', function (): void {
    $owner = User::factory()->create();

    $asset = EquipmentAsset::factory()->create([
        'name' => 'Tablet Press Line 1',
        'category' => EquipmentAssetCategory::Production,
        'criticality' => EquipmentAssetCriticality::Critical,
        'gxp_impact' => true,
        'status' => EquipmentAssetStatus::Active,
        'owner_id' => $owner,
    ])->refresh();

    expect($asset->asset_number)->toStartWith('EQA-')
        ->and($asset->category)->toBe(EquipmentAssetCategory::Production)
        ->and($asset->criticality)->toBe(EquipmentAssetCriticality::Critical)
        ->and($asset->gxp_impact)->toBeTrue()
        ->and($asset->owner?->is($owner))->toBeTrue();
});

it('owns equipment asset permissions and exposes the Filament resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:EquipmentAsset',
            'View:EquipmentAsset',
            'Create:EquipmentAsset',
            'Update:EquipmentAsset',
            'Manage:EquipmentAsset',
        )
        ->and(class_exists('App\\Filament\\Resources\\EquipmentAssets\\EquipmentAssetResource'))
        ->toBeTrue();
});
