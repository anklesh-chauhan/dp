<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Enums\EquipmentQualificationType;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\QMS\Models\EquipmentQualification;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the equipment qualification schema', function (): void {
    expect(Schema::hasColumns('equipment_qualifications', [
        'qualification_number',
        'equipment_asset_id',
        'type',
        'status',
        'protocol_title',
        'protocol_summary',
        'acceptance_criteria',
        'executed_by',
        'reviewed_by',
        'deviation_id',
        'started_at',
        'completed_at',
        'approved_at',
    ]))->toBeTrue()
        ->and(Schema::hasTable('equipment_qualification_events'))->toBeTrue();
});

it('persists equipment qualification fields distinct from csv validation', function (): void {
    $asset = EquipmentAsset::factory()->create();

    $qualification = EquipmentQualification::factory()->create([
        'equipment_asset_id' => $asset,
        'type' => EquipmentQualificationType::Iq,
        'status' => EquipmentQualificationStatus::Draft,
        'protocol_title' => 'IQ Protocol for Tablet Press',
    ])->refresh();

    expect($qualification->qualification_number)->toStartWith('EQ-')
        ->and($qualification->type)->toBe(EquipmentQualificationType::Iq)
        ->and($qualification->equipmentAsset?->is($asset))->toBeTrue()
        ->and($qualification->type->value)->not->toBe('csv_iq');
});

it('owns equipment qualification permissions and exposes the Filament resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:EquipmentQualification',
            'View:EquipmentQualification',
            'Create:EquipmentQualification',
            'Update:EquipmentQualification',
            'Execute:EquipmentQualification',
            'Review:EquipmentQualification',
            'Approve:EquipmentQualification',
            'Manage:EquipmentQualification',
        )
        ->and(class_exists('App\\Filament\\Resources\\EquipmentQualifications\\EquipmentQualificationResource'))
        ->toBeTrue();
});
