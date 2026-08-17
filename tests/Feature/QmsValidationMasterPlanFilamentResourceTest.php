<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Models\ValidationMasterPlan;
use App\Filament\Resources\ValidationMasterPlans\Pages\ListValidationMasterPlans;
use App\Filament\Resources\ValidationMasterPlans\Pages\ViewValidationMasterPlan;
use App\Filament\Resources\ValidationMasterPlans\ValidationMasterPlanResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:ValidationMasterPlan',
        'View:ValidationMasterPlan',
        'Create:ValidationMasterPlan',
        'Update:ValidationMasterPlan',
        'Approve:ValidationMasterPlan',
        'Retire:ValidationMasterPlan',
        'Manage:ValidationMasterPlan',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces QMS entitlement for the validation master plan filament resource', function (): void {
    expect(ValidationMasterPlanResource::canAccess())->toBeTrue()
        ->and(ValidationMasterPlanResource::getNavigationGroup())->toBe('QMS')
        ->and(ValidationMasterPlanResource::getNavigationSort())->toBe(15);

    config()->set('modules.enabled', ['dms']);

    expect(ValidationMasterPlanResource::canAccess())->toBeFalse()
        ->and(ValidationMasterPlanResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(ValidationMasterPlanResource::getUrl())->assertForbidden();
});

it('denies direct Livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListValidationMasterPlans::class)->assertForbidden();
});

it('delegates validation master plan lifecycle actions through the transition service', function (): void {
    $plan = ValidationMasterPlan::factory()->create(['status' => ValidationMasterPlanStatus::Draft]);

    Livewire::test(ViewValidationMasterPlan::class, ['record' => $plan->id])
        ->callAction('activate', ['reason' => 'VMP activated for the site.'])
        ->assertNotified();

    expect($plan->fresh()?->status)->toBe(ValidationMasterPlanStatus::Active)
        ->and($plan->auditEvents()->count())->toBe(1);
});
