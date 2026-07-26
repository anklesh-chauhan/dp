<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AuditFindingClassification;
use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\CapaType;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Services\AuditFindingCapaService;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    foreach (['Respond:AuditFinding', 'Create:Capa'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo(['Respond:AuditFinding', 'Create:Capa']);
    $this->owner = User::factory()->create();
    $this->finding = AuditFinding::factory()->create([
        'classification' => AuditFindingClassification::Nonconformity,
        'disposition' => AuditFindingDisposition::Open,
    ]);
});

it('atomically opens one traceable CAPA and advances the finding response', function (): void {
    $service = app(AuditFindingCapaService::class);
    $capa = $service->create(
        $this->finding,
        $this->actor,
        $this->owner,
        CapaType::CorrectiveAndPreventive,
        'Revise the control, retrain users, and verify sustained compliance.',
        'Critical nonconformity requires formal CAPA.',
        today()->addDays(45),
        today()->addDays(90),
    );
    $retry = $service->create(
        $this->finding,
        $this->actor,
        $this->owner,
        CapaType::Corrective,
        'Safe retry.',
        'Safe retry.',
        today()->addDays(30),
    );

    expect($retry->is($capa))->toBeTrue()
        ->and(Capa::query()->count())->toBe(1)
        ->and($capa->auditFinding?->is($this->finding))->toBeTrue()
        ->and($capa->deviation_id)->toBeNull()
        ->and($capa->owner?->is($this->owner))->toBeTrue()
        ->and($capa->type)->toBe(CapaType::CorrectiveAndPreventive)
        ->and($this->finding->fresh()?->disposition)->toBe(AuditFindingDisposition::ResponsePending)
        ->and($this->finding->fresh()?->auditEvents)->toHaveCount(1)
        ->and($this->finding->fresh()?->auditEvents->first()?->context)
        ->toBe(['capa_id' => $capa->id]);

    expect(fn () => $capa->update(['audit_finding_id' => null]))
        ->toThrow(LogicException::class);
});

it('keeps deviation-sourced CAPAs compatible and enforces exactly one source', function (): void {
    $deviationCapa = Capa::factory()->create();

    expect($deviationCapa->deviation_id)->not->toBeNull()
        ->and($deviationCapa->audit_finding_id)->toBeNull();

    expect(fn () => Capa::factory()->create([
        'deviation_id' => null,
        'audit_finding_id' => null,
    ]))->toThrow(LogicException::class);
});

it('rejects ineligible findings unauthorized actors invalid dates and disabled QMS', function (): void {
    $service = app(AuditFindingCapaService::class);
    $observation = AuditFinding::factory()->create([
        'classification' => AuditFindingClassification::Observation,
        'disposition' => AuditFindingDisposition::Open,
    ]);

    expect(fn () => $service->create(
        $observation,
        $this->actor,
        $this->owner,
        CapaType::Corrective,
        'Action plan.',
        'Not a nonconformity.',
        today()->addDays(30),
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->create(
        $this->finding,
        User::factory()->create(),
        $this->owner,
        CapaType::Corrective,
        'Action plan.',
        'Unauthorized.',
        today()->addDays(30),
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->create(
        $this->finding,
        $this->actor,
        $this->owner,
        CapaType::Corrective,
        'Action plan.',
        'Invalid dates.',
        today()->addDays(30),
        today()->addDays(20),
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->create(
        $this->finding,
        $this->actor,
        $this->owner,
        CapaType::Corrective,
        'Action plan.',
        'Disabled.',
        today()->addDays(30),
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(Capa::query()->count())->toBe(0);
});
