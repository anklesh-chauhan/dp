<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentAssetStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\QMS\Models\EquipmentAssetEvent;
use App\Domain\QMS\Services\EquipmentAssetTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'Update:EquipmentAsset',
        'Manage:EquipmentAsset',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->asset = EquipmentAsset::factory()->create(['status' => EquipmentAssetStatus::Active]);
});

it('records simple status transitions and signs decommission decisions', function (): void {
    $service = app(EquipmentAssetTransitionService::class);
    $service->transition($this->asset, EquipmentAssetStatus::Inactive, $this->actor, 'Taken offline for maintenance.');
    $decommissioned = $service->transition(
        $this->asset,
        EquipmentAssetStatus::Decommissioned,
        $this->actor,
        'Asset permanently retired.',
        [],
        '203.0.113.80',
        'QualiGxP-QMS-Test/1.0',
    );

    $events = $decommissioned->auditEvents()->orderBy('id')->get();
    $signed = $events->first(fn (EquipmentAssetEvent $event): bool => $event->to_status === EquipmentAssetStatus::Decommissioned);

    expect($decommissioned->status)->toBe(EquipmentAssetStatus::Decommissioned)
        ->and($events)->toHaveCount(2)
        ->and($signed?->signature_hash)->not->toBeNull()
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signed))->toBeTrue();
});

it('rejects missing reasons unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(EquipmentAssetTransitionService::class);

    expect(fn () => $service->transition(
        $this->asset,
        EquipmentAssetStatus::Inactive,
        $this->actor,
        ' ',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        $this->asset,
        EquipmentAssetStatus::Inactive,
        User::factory()->create(),
        'Deactivate asset.',
    ))->toThrow(AuthorizationException::class);

    $this->asset->update(['status' => EquipmentAssetStatus::Decommissioned]);

    expect(fn () => $service->transition(
        $this->asset,
        EquipmentAssetStatus::Active,
        $this->actor,
        'Cannot reactivate.',
    ))->toThrow(ValidationException::class);

    $this->asset->update(['status' => EquipmentAssetStatus::Active]);
    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->asset,
        EquipmentAssetStatus::Inactive,
        $this->actor,
        'Deactivate asset.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(EquipmentAssetEvent::query()->count())->toBe(0);
});
