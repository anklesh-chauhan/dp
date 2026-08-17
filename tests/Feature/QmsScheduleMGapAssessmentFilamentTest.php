<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Filament\Resources\ScheduleMGapAssessments\Pages\ListScheduleMGapAssessments;
use App\Filament\Resources\ScheduleMGapAssessments\Pages\ViewScheduleMGapAssessment;
use App\Filament\Resources\ScheduleMGapAssessments\ScheduleMGapAssessmentResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:ScheduleMGapAssessment',
        'View:ScheduleMGapAssessment',
        'Create:ScheduleMGapAssessment',
        'Update:ScheduleMGapAssessment',
        'Conduct:ScheduleMGapAssessment',
        'Approve:ScheduleMGapAssessment',
        'Close:ScheduleMGapAssessment',
        'Manage:ScheduleMGapAssessment',
        'Export:InspectorEvidencePack',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces qms entitlement for the schedule m gap assessment resource', function (): void {
    expect(ScheduleMGapAssessmentResource::canAccess())->toBeTrue()
        ->and(ScheduleMGapAssessmentResource::getNavigationGroup())->toBe('QMS')
        ->and(ScheduleMGapAssessmentResource::getNavigationSort())->toBe(18);

    config()->set('modules.enabled', ['dms']);

    expect(ScheduleMGapAssessmentResource::canAccess())->toBeFalse()
        ->and(ScheduleMGapAssessmentResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(ScheduleMGapAssessmentResource::getUrl())->assertForbidden();
});

it('denies direct livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListScheduleMGapAssessments::class)->assertForbidden();
});

it('delegates begin assessment through the transition service', function (): void {
    $assessment = ScheduleMGapAssessment::factory()->withPartIChecklist()->create([
        'status' => ScheduleMGapAssessmentStatus::Draft,
    ]);

    Livewire::test(ViewScheduleMGapAssessment::class, ['record' => $assessment->id])
        ->callAction('begin', ['reason' => 'Schedule M gap assessment started for the site.'])
        ->assertNotified();

    expect($assessment->fresh()?->status)->toBe(ScheduleMGapAssessmentStatus::InProgress)
        ->and($assessment->auditEvents()->count())->toBe(1);
});
