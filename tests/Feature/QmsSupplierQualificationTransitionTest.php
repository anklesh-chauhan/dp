<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Enums\SupplierRiskLevel;
use App\Domain\QMS\Models\SupplierQualification;
use App\Domain\QMS\Models\SupplierQualificationEvent;
use App\Domain\QMS\Services\SupplierQualificationTransitionService;
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
        'Assess:SupplierQualification',
        'Audit:SupplierQualification',
        'Approve:SupplierQualification',
        'Suspend:SupplierQualification',
        'Disqualify:SupplierQualification',
        'Review:SupplierQualification',
        'Manage:SupplierQualification',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->qualification = SupplierQualification::factory()->create([
        'risk_level' => SupplierRiskLevel::Critical,
        'qualification_expires_at' => today()->addYear(),
        'next_review_at' => today()->addMonths(6),
    ]);
});

it('records assessment audit qualification suspension and reinstatement with signed history', function (): void {
    $service = app(SupplierQualificationTransitionService::class);
    $service->transition(
        $this->qualification,
        SupplierQualificationStatus::UnderAssessment,
        $this->actor,
        'Qualification assessment opened.',
    );
    $service->transition(
        $this->qualification,
        SupplierQualificationStatus::AuditRequired,
        $this->actor,
        'Critical-risk supplier requires an audit.',
    );
    $this->qualification->update(['audit_completed_at' => now()]);
    $qualified = $service->transition(
        $this->qualification,
        SupplierQualificationStatus::Qualified,
        $this->actor,
        'Audit evidence accepted.',
        'Quality system and site controls meet approved requirements.',
        ipAddress: '203.0.113.81',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );
    $suspended = $service->transition(
        $qualified,
        SupplierQualificationStatus::Suspended,
        $this->actor,
        'Serious delivery deviation requires temporary suspension.',
    );
    $reassessment = $service->transition(
        $suspended,
        SupplierQualificationStatus::UnderAssessment,
        $this->actor,
        'Corrective evidence received for reinstatement assessment.',
        'Supplier remediation package accepted for reassessment.',
    );

    $events = $reassessment->auditEvents()->orderBy('id')->get();
    $qualificationEvent = $events->get(2);

    expect($reassessment->status)->toBe(SupplierQualificationStatus::UnderAssessment)
        ->and($reassessment->qualification_started_at)->not->toBeNull()
        ->and($reassessment->approved_by)->toBe($this->actor->id)
        ->and($reassessment->qualified_at)->not->toBeNull()
        ->and($reassessment->suspended_at)->toBeNull()
        ->and($events)->toHaveCount(5)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($qualificationEvent?->signatureMeaning())->toBe(SupplierQualificationStatus::Qualified->value)
        ->and($qualificationEvent?->signatureIpAddress())->toBe('203.0.113.81')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($qualificationEvent))->toBeTrue();

    expect(fn () => $qualificationEvent?->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('enforces risk-based audit evidence rationale dates and approver separation', function (): void {
    $service = app(SupplierQualificationTransitionService::class);
    $this->qualification->update(['status' => SupplierQualificationStatus::UnderAssessment]);

    expect(fn () => $service->transition(
        $this->qualification,
        SupplierQualificationStatus::Qualified,
        $this->actor,
        'No audit evidence.',
        'Attempted qualification.',
    ))->toThrow(ValidationException::class);

    $this->qualification->update([
        'risk_level' => SupplierRiskLevel::Low,
        'next_review_at' => today()->addYears(2),
        'qualification_expires_at' => today()->addYear(),
    ]);

    expect(fn () => $service->transition(
        $this->qualification,
        SupplierQualificationStatus::Qualified,
        $this->actor,
        'Invalid review date.',
        'Qualification rationale.',
    ))->toThrow(ValidationException::class);

    $this->qualification->update([
        'next_review_at' => today()->addMonths(6),
        'owner_id' => $this->actor->id,
    ]);

    expect(fn () => $service->transition(
        $this->qualification,
        SupplierQualificationStatus::Qualified,
        $this->actor,
        'Owner self-approval.',
        'Qualification rationale.',
    ))->toThrow(ValidationException::class)
        ->and(SupplierQualificationEvent::query()->count())->toBe(0);
});

it('rejects unauthorized invalid expiry and disabled transitions without events', function (): void {
    $service = app(SupplierQualificationTransitionService::class);

    expect(fn () => $service->transition(
        $this->qualification,
        SupplierQualificationStatus::UnderAssessment,
        User::factory()->create(),
        'Unauthorized.',
    ))->toThrow(AuthorizationException::class);

    $this->qualification->update([
        'status' => SupplierQualificationStatus::Qualified,
        'qualification_expires_at' => today()->addDay(),
    ]);

    expect(fn () => $service->transition(
        $this->qualification,
        SupplierQualificationStatus::Expired,
        $this->actor,
        'Expiry is not elapsed.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->qualification,
        SupplierQualificationStatus::Suspended,
        $this->actor,
        'Disabled.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(SupplierQualificationEvent::query()->count())->toBe(0);
});
