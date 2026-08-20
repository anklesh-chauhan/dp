<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentCalibrationResult;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\EquipmentCalibration;
use App\Domain\QMS\Models\EquipmentCalibrationEvent;
use App\Domain\QMS\Services\EquipmentCalibrationTransitionService;
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
        'Perform:EquipmentCalibration',
        'Verify:EquipmentCalibration',
        'Manage:EquipmentCalibration',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->calibration = EquipmentCalibration::factory()->create();
});

it('records an attributable timeline signs completions and requires deviation for out of tolerance', function (): void {
    $service = app(EquipmentCalibrationTransitionService::class);
    $service->transition($this->calibration, EquipmentCalibrationStatus::InProgress, $this->actor, 'Calibration started.');
    $completed = $service->transition(
        $this->calibration,
        EquipmentCalibrationStatus::Completed,
        $this->actor,
        'Within tolerance.',
        ['result' => EquipmentCalibrationResult::Pass->value, 'certificate_reference' => 'CERT-1'],
        '203.0.113.90',
        'QualiGxP-QMS-Test/1.0',
    );

    $events = $completed->auditEvents()->orderBy('id')->get();
    $signed = $events->first(fn (EquipmentCalibrationEvent $event): bool => $event->to_status === EquipmentCalibrationStatus::Completed);

    expect($completed->status)->toBe(EquipmentCalibrationStatus::Completed)
        ->and($completed->result)->toBe(EquipmentCalibrationResult::Pass)
        ->and($completed->certificate_reference)->toBe('CERT-1')
        ->and($completed->performed_at)->not->toBeNull()
        ->and($events)->toHaveCount(2)
        ->and($signed?->signature_hash)->not->toBeNull()
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signed))->toBeTrue();

    $verified = $service->verify($completed, $this->actor, 'Independent verification complete.');
    expect($verified->verified_by)->toBe($this->actor->getKey())
        ->and($verified->auditEvents()->count())->toBe(3);

    $oot = EquipmentCalibration::factory()->create(['status' => EquipmentCalibrationStatus::InProgress]);
    expect(fn () => $service->transition(
        $oot,
        EquipmentCalibrationStatus::OutOfTolerance,
        $this->actor,
        'Instrument failed as-found.',
    ))->toThrow(ValidationException::class);

    $deviation = Deviation::factory()->create();
    $result = $service->transition(
        $oot,
        EquipmentCalibrationStatus::OutOfTolerance,
        $this->actor,
        'Instrument failed as-found.',
        ['deviation_id' => $deviation->getKey(), 'signature' => 'secret'],
    );

    expect($result->status)->toBe(EquipmentCalibrationStatus::OutOfTolerance)
        ->and($result->result)->toBe(EquipmentCalibrationResult::OutOfTolerance)
        ->and($result->deviation_id)->toBe($deviation->getKey())
        ->and($result->auditEvents()->latest('id')->first()?->context)->toMatchArray([
            'deviation_id' => $deviation->getKey(),
        ])
        ->and($result->auditEvents()->latest('id')->first()?->signatureContentDigest())->not->toBeNull();
});

it('rejects missing reasons unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(EquipmentCalibrationTransitionService::class);

    expect(fn () => $service->transition(
        $this->calibration,
        EquipmentCalibrationStatus::InProgress,
        $this->actor,
        ' ',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        $this->calibration,
        EquipmentCalibrationStatus::InProgress,
        User::factory()->create(),
        'Begin calibration.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->transition(
        $this->calibration,
        EquipmentCalibrationStatus::Completed,
        $this->actor,
        'Invalid direct completion.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->calibration,
        EquipmentCalibrationStatus::InProgress,
        $this->actor,
        'Begin calibration.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(EquipmentCalibrationEvent::query()->count())->toBe(0);
});
