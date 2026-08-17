<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Domain\QMS\Services\ProductQualityReviewTransitionService;
use App\Exceptions\ModuleNotEnabledException;
use App\Filament\Resources\EquipmentCalibrations\EquipmentCalibrationResource;
use App\Filament\Resources\EquipmentMaintenances\EquipmentMaintenanceResource;
use App\Filament\Resources\LaboratoryOosEvents\LaboratoryOosEventResource;
use App\Filament\Resources\ProductQualityReviews\ProductQualityReviewResource;
use App\Filament\Resources\ProductRecalls\ProductRecallResource;
use App\Filament\Resources\ProductReturns\ProductReturnResource;
use App\Filament\Resources\ScheduleMGapAssessments\ScheduleMGapAssessmentResource;
use App\Filament\Resources\SiteMasterFiles\SiteMasterFileResource;
use App\Filament\Resources\ValidationMasterPlans\ValidationMasterPlanResource;
use App\Models\User;
use Database\Seeders\CoreModuleSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DmsModuleSeeder;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('replays empty-database seeding twice without duplicate Schedule M permissions', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $expectedAdministratorPermissions = [
        ...CoreModuleSeeder::PERMISSIONS,
        ...DmsModuleSeeder::PERMISSIONS,
        ...QmsModuleSeeder::PERMISSIONS,
    ];

    $scheduleMPermissions = [
        'ViewAny:ProductQualityReview',
        'Approve:ProductQualityReview',
        'ViewAny:ProductRecall',
        'Initiate:ProductRecall',
        'ViewAny:ProductReturn',
        'ViewAny:LaboratoryOosEvent',
        'ViewAny:ValidationMasterPlan',
        'ViewAny:EquipmentCalibration',
        'Perform:EquipmentCalibration',
        'ViewAny:EquipmentMaintenance',
        'ViewAny:ScheduleMGapAssessment',
        'Export:InspectorEvidencePack',
        'Publish:SiteMasterFile',
        'Assign:UserCompetency',
        'View:QualityMetrics',
    ];

    expect(Permission::query()->pluck('name')->all())
        ->toHaveCount(count($expectedAdministratorPermissions))
        ->toEqualCanonicalizing($expectedAdministratorPermissions)
        ->and(Permission::query()->whereIn('name', $scheduleMPermissions)->count())
        ->toBe(count($scheduleMPermissions))
        ->and(Role::findByName('sop administrator', 'web')->permissions->pluck('name')->all())
        ->toHaveCount(count($expectedAdministratorPermissions))
        ->toEqualCanonicalizing($expectedAdministratorPermissions)
        ->and(QmsModuleSeeder::PERMISSIONS)->toContain(...$scheduleMPermissions);
});

it('exposes Schedule M Filament resource classes for shipped domains', function (): void {
    $resources = [
        ProductQualityReviewResource::class,
        ProductRecallResource::class,
        ProductReturnResource::class,
        LaboratoryOosEventResource::class,
        ValidationMasterPlanResource::class,
        EquipmentCalibrationResource::class,
        EquipmentMaintenanceResource::class,
        ScheduleMGapAssessmentResource::class,
        SiteMasterFileResource::class,
    ];

    foreach ($resources as $resource) {
        expect(class_exists($resource))->toBeTrue()
            ->and($resource::getNavigationGroup())->toBe('QMS');
    }
});

it('blocks a sample Schedule M transition when QMS is disabled after empty migrate', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    $actor = User::factory()->create();
    $review = ProductQualityReview::factory()->create();

    config()->set('modules.enabled', ['dms']);

    expect(fn () => app(ProductQualityReviewTransitionService::class)->transition(
        $review,
        ProductQualityReviewStatus::InProgress,
        $actor,
        'Release validation sample.',
    ))->toThrow(ModuleNotEnabledException::class);
});

it('denies ProductQualityReview ProductRecall and ScheduleMGapAssessment resources when QMS is disabled', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach ([
        'ViewAny:ProductQualityReview',
        'ViewAny:ProductRecall',
        'ViewAny:ScheduleMGapAssessment',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo([
        'ViewAny:ProductQualityReview',
        'ViewAny:ProductRecall',
        'ViewAny:ScheduleMGapAssessment',
    ]);
    $this->actingAs($user);

    $resources = [
        ProductQualityReviewResource::class,
        ProductRecallResource::class,
        ScheduleMGapAssessmentResource::class,
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

    $this->get(ProductQualityReviewResource::getUrl())->assertForbidden();
    $this->get(ProductRecallResource::getUrl())->assertForbidden();
    $this->get(ScheduleMGapAssessmentResource::getUrl())->assertForbidden();
});
