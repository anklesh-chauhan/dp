<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AuditFindingClassification;
use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\CapaType;
use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ComplaintType;
use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Models\RiskAssessment;
use App\Filament\Resources\AuditFindings\AuditFindingResource;
use App\Filament\Resources\AuditFindings\Pages\ListAuditFindings;
use App\Filament\Resources\AuditFindings\Pages\ViewAuditFinding;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Filament\Resources\Complaints\Pages\ListComplaints;
use App\Filament\Resources\Complaints\Pages\ViewComplaint;
use App\Filament\Resources\InternalAudits\InternalAuditResource;
use App\Filament\Resources\InternalAudits\Pages\ListInternalAudits;
use App\Filament\Resources\ManagementReviews\ManagementReviewResource;
use App\Filament\Resources\ManagementReviews\Pages\ListManagementReviews;
use App\Filament\Resources\ManagementReviews\Pages\ViewManagementReview;
use App\Filament\Resources\RiskAssessments\Pages\ListRiskAssessments;
use App\Filament\Resources\RiskAssessments\Pages\ViewRiskAssessment;
use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use App\Filament\Resources\SupplierQualifications\Pages\ListSupplierQualifications;
use App\Filament\Resources\SupplierQualifications\SupplierQualificationResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:Complaint',
        'View:Complaint',
        'Create:Complaint',
        'Update:Complaint',
        'Assess:Complaint',
        'Investigate:Complaint',
        'Create:Deviation',
        'ViewAny:InternalAudit',
        'View:InternalAudit',
        'ViewAny:AuditFinding',
        'View:AuditFinding',
        'Respond:AuditFinding',
        'Create:Capa',
        'ViewAny:RiskAssessment',
        'View:RiskAssessment',
        'Review:RiskAssessment',
        'ViewAny:SupplierQualification',
        'View:SupplierQualification',
        'ViewAny:ManagementReview',
        'View:ManagementReview',
        'Schedule:ManagementReview',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces QMS entitlement for every Phase 14 resource', function (): void {
    $resources = [
        ComplaintResource::class,
        InternalAuditResource::class,
        AuditFindingResource::class,
        RiskAssessmentResource::class,
        SupplierQualificationResource::class,
        ManagementReviewResource::class,
    ];

    foreach ($resources as $resource) {
        expect($resource::canAccess())->toBeTrue()
            ->and($resource::getNavigationGroup())->toBe('QMS');
    }

    config()->set('modules.enabled', ['dms']);

    foreach ($resources as $resource) {
        expect($resource::canAccess())->toBeFalse()
            ->and($resource::shouldRegisterNavigation())->toBeFalse();
    }

    $this->get(ComplaintResource::getUrl())->assertForbidden();
});

it('denies direct Livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListComplaints::class)->assertForbidden();
    Livewire::test(ListInternalAudits::class)->assertForbidden();
    Livewire::test(ListAuditFindings::class)->assertForbidden();
    Livewire::test(ListRiskAssessments::class)->assertForbidden();
    Livewire::test(ListSupplierQualifications::class)->assertForbidden();
    Livewire::test(ListManagementReviews::class)->assertForbidden();
});

it('delegates complaint risk and management review lifecycle actions through transition services', function (): void {
    $complaint = Complaint::factory()->create(['status' => ComplaintStatus::Draft]);
    Livewire::test(ViewComplaint::class, ['record' => $complaint->id])
        ->callAction('markReceived', ['reason' => 'Complaint intake complete.'])
        ->assertNotified();
    expect($complaint->fresh()?->status)->toBe(ComplaintStatus::Received)
        ->and($complaint->auditEvents()->count())->toBe(1);

    $risk = RiskAssessment::factory()->create(['status' => RiskAssessmentStatus::Draft]);
    Livewire::test(ViewRiskAssessment::class, ['record' => $risk->id])
        ->callAction('submitReview', ['reason' => 'Initial scoring ready for review.'])
        ->assertNotified();
    expect($risk->fresh()?->status)->toBe(RiskAssessmentStatus::InReview)
        ->and($risk->auditEvents()->count())->toBe(1);

    $review = ManagementReview::factory()->create(['status' => ManagementReviewStatus::Draft]);
    Livewire::test(ViewManagementReview::class, ['record' => $review->id])
        ->callAction('schedule', ['reason' => 'Meeting and inputs confirmed.'])
        ->assertNotified();
    expect($review->fresh()?->status)->toBe(ManagementReviewStatus::Scheduled)
        ->and($review->auditEvents()->count())->toBe(1);
});

it('opens a deviation from a product-quality complaint through the Filament handoff action', function (): void {
    $complaint = Complaint::factory()->create([
        'status' => ComplaintStatus::UnderAssessment,
        'type' => ComplaintType::ProductQuality,
    ]);

    Livewire::test(ViewComplaint::class, ['record' => $complaint->id])
        ->callAction('openDeviation', [
            'severity' => DeviationSeverity::Major->value,
            'immediate_actions' => 'Quarantined affected lots.',
            'investigation_due_at' => today()->addDays(10)->format('Y-m-d'),
            'reason' => 'Product quality requires deviation investigation.',
        ])
        ->assertNotified();

    $deviation = Deviation::query()->sole();

    expect($deviation->complaint_id)->toBe($complaint->id)
        ->and($deviation->severity)->toBe(DeviationSeverity::Major)
        ->and($complaint->fresh()?->status)->toBe(ComplaintStatus::UnderInvestigation);
});

it('opens a CAPA from an actionable nonconformity through the Filament handoff action', function (): void {
    $finding = AuditFinding::factory()->create([
        'classification' => AuditFindingClassification::Nonconformity,
        'disposition' => AuditFindingDisposition::Open,
    ]);
    $owner = User::factory()->create();

    Livewire::test(ViewAuditFinding::class, ['record' => $finding->id])
        ->callAction('openCapa', [
            'owner_id' => $owner->id,
            'type' => CapaType::Corrective->value,
            'action_plan' => 'Correct the documented nonconformity and revise the procedure.',
            'due_at' => today()->addDays(30)->format('Y-m-d'),
            'effectiveness_due_at' => today()->addDays(60)->format('Y-m-d'),
            'reason' => 'Nonconformity requires CAPA ownership.',
        ])
        ->assertNotified();

    $capa = Capa::query()->sole();

    expect($capa->audit_finding_id)->toBe($finding->id)
        ->and($capa->owner_id)->toBe($owner->id)
        ->and($finding->fresh()?->disposition)->toBe(AuditFindingDisposition::ResponsePending);
});
