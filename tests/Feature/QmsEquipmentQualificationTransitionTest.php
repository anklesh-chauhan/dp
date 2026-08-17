<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\EquipmentQualification;
use App\Domain\QMS\Models\EquipmentQualificationEvent;
use App\Domain\QMS\Services\EquipmentQualificationTransitionService;
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
        'Execute:EquipmentQualification',
        'Review:EquipmentQualification',
        'Approve:EquipmentQualification',
        'Manage:EquipmentQualification',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->qualification = EquipmentQualification::factory()->create();
});

it('records an attributable timeline signs approvals and links deviations on failure', function (): void {
    $service = app(EquipmentQualificationTransitionService::class);
    $service->transition($this->qualification, EquipmentQualificationStatus::InProgress, $this->actor, 'Execution started.');
    $service->transition($this->qualification, EquipmentQualificationStatus::UnderReview, $this->actor, 'Submitted for QA review.');
    $approved = $service->transition(
        $this->qualification,
        EquipmentQualificationStatus::Approved,
        $this->actor,
        'Protocol met acceptance criteria.',
        [],
        '203.0.113.90',
        'QualiGxP-QMS-Test/1.0',
    );

    $events = $approved->auditEvents()->orderBy('id')->get();
    $signed = $events->first(fn (EquipmentQualificationEvent $event): bool => $event->to_status === EquipmentQualificationStatus::Approved);

    expect($approved->status)->toBe(EquipmentQualificationStatus::Approved)
        ->and($approved->started_at)->not->toBeNull()
        ->and($approved->completed_at)->not->toBeNull()
        ->and($approved->approved_at)->not->toBeNull()
        ->and($events)->toHaveCount(3)
        ->and($signed?->signature_hash)->not->toBeNull()
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signed))->toBeTrue();

    $failed = EquipmentQualification::factory()->create(['status' => EquipmentQualificationStatus::InProgress]);
    $deviation = Deviation::factory()->create();
    $result = $service->transition(
        $failed,
        EquipmentQualificationStatus::Failed,
        $this->actor,
        'Failed OQ acceptance criteria.',
        ['deviation_id' => $deviation->getKey(), 'signature' => 'secret'],
    );

    expect($result->status)->toBe(EquipmentQualificationStatus::Failed)
        ->and($result->deviation_id)->toBe($deviation->getKey())
        ->and($result->auditEvents()->latest('id')->first()?->context)->toBe([
            'deviation_id' => $deviation->getKey(),
        ]);
});

it('rejects missing reasons unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(EquipmentQualificationTransitionService::class);

    expect(fn () => $service->transition(
        $this->qualification,
        EquipmentQualificationStatus::InProgress,
        $this->actor,
        ' ',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        $this->qualification,
        EquipmentQualificationStatus::InProgress,
        User::factory()->create(),
        'Begin execution.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->transition(
        $this->qualification,
        EquipmentQualificationStatus::Approved,
        $this->actor,
        'Invalid direct approval.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->qualification,
        EquipmentQualificationStatus::InProgress,
        $this->actor,
        'Begin execution.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(EquipmentQualificationEvent::query()->count())->toBe(0);
});
