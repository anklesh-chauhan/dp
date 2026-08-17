<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\QMS\Models\EquipmentMaintenance;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the equipment maintenance schema', function (): void {
    expect(Schema::hasColumns('equipment_maintenances', [
        'work_order_number',
        'equipment_asset_id',
        'status',
        'due_at',
        'completed_at',
        'description',
        'performed_by',
        'notes',
    ]))->toBeTrue()
        ->and(Schema::hasTable('equipment_maintenance_events'))->toBeTrue();
});

it('persists equipment maintenance work orders on an asset', function (): void {
    $asset = EquipmentAsset::factory()->create();

    $maintenance = EquipmentMaintenance::factory()->create([
        'equipment_asset_id' => $asset,
        'status' => EquipmentMaintenanceStatus::Planned,
        'description' => 'Quarterly preventive maintenance',
    ])->refresh();

    expect($maintenance->work_order_number)->toStartWith('PM-')
        ->and($maintenance->status)->toBe(EquipmentMaintenanceStatus::Planned)
        ->and($maintenance->equipmentAsset?->is($asset))->toBeTrue()
        ->and($asset->maintenances()->count())->toBe(1);
});

it('owns equipment maintenance permissions and exposes the Filament resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:EquipmentMaintenance',
            'View:EquipmentMaintenance',
            'Create:EquipmentMaintenance',
            'Update:EquipmentMaintenance',
            'Perform:EquipmentMaintenance',
            'Manage:EquipmentMaintenance',
        )
        ->and(class_exists('App\\Filament\\Resources\\EquipmentMaintenances\\EquipmentMaintenanceResource'))
        ->toBeTrue();
});
