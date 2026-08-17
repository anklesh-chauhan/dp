<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentCalibrationResult;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\QMS\Models\EquipmentCalibration;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the equipment calibration schema', function (): void {
    expect(Schema::hasColumns('equipment_calibrations', [
        'calibration_number',
        'equipment_asset_id',
        'status',
        'due_at',
        'performed_at',
        'next_due_at',
        'performed_by',
        'verified_by',
        'result',
        'certificate_reference',
        'notes',
        'deviation_id',
    ]))->toBeTrue()
        ->and(Schema::hasTable('equipment_calibration_events'))->toBeTrue();
});

it('persists equipment calibration fields on an asset', function (): void {
    $asset = EquipmentAsset::factory()->create();

    $calibration = EquipmentCalibration::factory()->create([
        'equipment_asset_id' => $asset,
        'status' => EquipmentCalibrationStatus::Scheduled,
        'result' => EquipmentCalibrationResult::Pending,
        'due_at' => now()->addWeek(),
    ])->refresh();

    expect($calibration->calibration_number)->toStartWith('CAL-')
        ->and($calibration->status)->toBe(EquipmentCalibrationStatus::Scheduled)
        ->and($calibration->equipmentAsset?->is($asset))->toBeTrue()
        ->and($asset->calibrations()->count())->toBe(1);
});

it('owns equipment calibration permissions and exposes the Filament resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:EquipmentCalibration',
            'View:EquipmentCalibration',
            'Create:EquipmentCalibration',
            'Update:EquipmentCalibration',
            'Perform:EquipmentCalibration',
            'Verify:EquipmentCalibration',
            'Manage:EquipmentCalibration',
        )
        ->and(class_exists('App\\Filament\\Resources\\EquipmentCalibrations\\EquipmentCalibrationResource'))
        ->toBeTrue();
});
