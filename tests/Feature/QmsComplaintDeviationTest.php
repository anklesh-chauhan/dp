<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ComplaintType;
use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Services\ComplaintDeviationService;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach (['Investigate:Complaint', 'Create:Deviation'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo(['Investigate:Complaint', 'Create:Deviation']);
    $this->complaint = Complaint::factory()->create([
        'status' => ComplaintStatus::UnderAssessment,
        'type' => ComplaintType::ProductQuality,
    ]);
});

it('atomically opens one traceable deviation and advances the complaint', function (): void {
    $service = app(ComplaintDeviationService::class);
    $deviation = $service->create(
        $this->complaint,
        $this->actor,
        DeviationSeverity::Critical,
        'Product-quality investigation required.',
        'Affected stock quarantined.',
        today()->addDays(14),
    );
    $retry = $service->create(
        $this->complaint,
        $this->actor,
        DeviationSeverity::Minor,
        'Safe retry.',
    );

    expect($retry->is($deviation))->toBeTrue()
        ->and(Deviation::query()->count())->toBe(1)
        ->and($deviation->complaint?->is($this->complaint))->toBeTrue()
        ->and($this->complaint->fresh()?->status)->toBe(ComplaintStatus::UnderInvestigation)
        ->and($this->complaint->fresh()?->auditEvents)->toHaveCount(1)
        ->and($this->complaint->fresh()?->auditEvents->first()?->context)
        ->toBe(['deviation_id' => $deviation->id])
        ->and($deviation->severity)->toBe(DeviationSeverity::Critical)
        ->and($deviation->immediate_actions)->toBe('Affected stock quarantined.')
        ->and($deviation->investigation_due_at?->toDateString())->toBe(today()->addDays(14)->toDateString());

    expect(fn () => $deviation->update(['complaint_id' => null]))
        ->toThrow(LogicException::class);
});

it('rejects ineligible complaints unauthorized actors and disabled QMS', function (): void {
    $service = app(ComplaintDeviationService::class);
    $ineligible = Complaint::factory()->create([
        'status' => ComplaintStatus::UnderAssessment,
        'type' => ComplaintType::MedicalInformation,
    ]);

    expect(fn () => $service->create(
        $ineligible,
        $this->actor,
        DeviationSeverity::Major,
        'Not a product-quality complaint.',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->create(
        $this->complaint,
        User::factory()->create(),
        DeviationSeverity::Major,
        'Unauthorized.',
    ))->toThrow(AuthorizationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->create(
        $this->complaint,
        $this->actor,
        DeviationSeverity::Major,
        'Disabled module.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(Deviation::query()->count())->toBe(0);
});
