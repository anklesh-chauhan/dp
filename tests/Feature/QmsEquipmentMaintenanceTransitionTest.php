<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Models\EquipmentMaintenance;
use App\Domain\QMS\Models\EquipmentMaintenanceEvent;
use App\Domain\QMS\Services\EquipmentMaintenanceTransitionService;
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
        'Perform:EquipmentMaintenance',
        'Manage:EquipmentMaintenance',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->maintenance = EquipmentMaintenance::factory()->create();
});

it('records an attributable timeline and signs completion', function (): void {
    $service = app(EquipmentMaintenanceTransitionService::class);
    $service->transition($this->maintenance, EquipmentMaintenanceStatus::InProgress, $this->actor, 'PM started.');
    $completed = $service->transition(
        $this->maintenance,
        EquipmentMaintenanceStatus::Completed,
        $this->actor,
        'PM completed per schedule.',
        ['notes' => 'Lubrication and inspection complete.'],
        '203.0.113.90',
        'QualiGxP-QMS-Test/1.0',
    );

    $events = $completed->auditEvents()->orderBy('id')->get();
    $signed = $events->first(fn (EquipmentMaintenanceEvent $event): bool => $event->to_status === EquipmentMaintenanceStatus::Completed);

    expect($completed->status)->toBe(EquipmentMaintenanceStatus::Completed)
        ->and($completed->completed_at)->not->toBeNull()
        ->and($completed->notes)->toBe('Lubrication and inspection complete.')
        ->and($events)->toHaveCount(2)
        ->and($signed?->signature_hash)->not->toBeNull()
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signed))->toBeTrue();
});

it('rejects missing reasons unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(EquipmentMaintenanceTransitionService::class);

    expect(fn () => $service->transition(
        $this->maintenance,
        EquipmentMaintenanceStatus::InProgress,
        $this->actor,
        ' ',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        $this->maintenance,
        EquipmentMaintenanceStatus::InProgress,
        User::factory()->create(),
        'Begin PM.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->transition(
        $this->maintenance,
        EquipmentMaintenanceStatus::Completed,
        $this->actor,
        'Invalid direct completion.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->maintenance,
        EquipmentMaintenanceStatus::InProgress,
        $this->actor,
        'Begin PM.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(EquipmentMaintenanceEvent::query()->count())->toBe(0);
});
