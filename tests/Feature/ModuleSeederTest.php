<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Database\Seeders\AiModuleSeeder;
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

it('seeds only core and DMS permissions for a DMS installation', function (): void {
    config()->set('modules.enabled', ['dms']);

    $this->seed(DatabaseSeeder::class);

    $expectedPermissions = [
        ...CoreModuleSeeder::PERMISSIONS,
        ...DmsModuleSeeder::PERMISSIONS,
    ];

    expect(Permission::query()->pluck('name')->all())
        ->toHaveCount(count($expectedPermissions))
        ->toEqualCanonicalizing($expectedPermissions)
        ->and(Permission::query()->whereIn('name', AiModuleSeeder::PERMISSIONS)->exists())
        ->toBeFalse();

    expect(Role::findByName('sop administrator', 'web')->permissions->pluck('name')->all())
        ->toEqualCanonicalizing($expectedPermissions)
        ->and(Role::findByName('sop maker', 'web')->hasPermissionTo('Create:ControlledDocument'))
        ->toBeTrue()
        ->and(Role::findByName('sop maker', 'web')->hasPermissionTo('Revise:ControlledDocument'))
        ->toBeTrue()
        ->and(Role::findByName('document controller', 'web')->hasPermissionTo('Issue:DocumentIssuance'))
        ->toBeTrue()
        ->and(Role::findByName('document controller', 'web')->hasPermissionTo('View:DocumentExecution'))
        ->toBeTrue()
        ->and(Role::findByName('document controller', 'web')->hasPermissionTo('Update:DocumentExecution'))
        ->toBeFalse()
        ->and(Role::findByName('gmp record executor', 'web')->hasPermissionTo('Update:DocumentExecution'))
        ->toBeTrue()
        ->and(Role::findByName('gmp record executor', 'web')->hasPermissionTo('Submit:DocumentExecution'))
        ->toBeTrue()
        ->and(Role::findByName('gmp record executor', 'web')->hasPermissionTo('Review:DocumentExecution'))
        ->toBeFalse()
        ->and(Role::findByName('production supervisor', 'web')->hasPermissionTo('Review:DocumentExecution'))
        ->toBeTrue()
        ->and(Role::findByName('production supervisor', 'web')->hasPermissionTo('Approve:DocumentExecution'))
        ->toBeFalse()
        ->and(Role::findByName('qa reviewer', 'web')->hasPermissionTo('Approve:DocumentExecution'))
        ->toBeTrue()
        ->and(Role::findByName('qa reviewer', 'web')->hasPermissionTo('Update:DocumentExecution'))
        ->toBeFalse()
        ->and(User::query()->where('email', 'RecordExecutor@example.com')->firstOrFail()->hasRole('gmp record executor'))
        ->toBeTrue()
        ->and(User::query()->where('email', 'ProductionSupervisor@example.com')->firstOrFail()->hasRole('production supervisor'))
        ->toBeTrue();
});

it('assigns QA department and demo designations to distinct DMS users', function (): void {
    config()->set('modules.enabled', ['dms']);

    $this->seed(DatabaseSeeder::class);

    $qaId = Department::query()->where('code', 'QA')->valueOrFail('id');

    $expected = [
        'Maker@example.com' => ['sop maker', 'SOP_MAKER', 'SOP Maker'],
        'Checker@example.com' => ['sop checker', 'SOP_CHK', 'SOP Checker'],
        'Approver@example.com' => ['sop approver', 'SOP_APR', 'SOP Approver'],
        'DocumentController@example.com' => ['document controller', 'DOC_CTRL', 'Document Controller'],
        'QaReviewer@example.com' => ['qa reviewer', 'QA_REV', 'QA Reviewer'],
        'RecordExecutor@example.com' => ['gmp record executor', 'GMP_EXEC', 'GMP Record Executor'],
        'ProductionSupervisor@example.com' => ['production supervisor', 'PROD_SUP', 'Production Supervisor'],
    ];

    foreach ($expected as $email => [$role, $designationCode, $designationName]) {
        $user = User::query()->where('email', $email)->firstOrFail();

        expect($user->department_id)->toBe($qaId)
            ->and($user->hasRole($role))->toBeTrue()
            ->and($user->designation?->code)->toBe($designationCode)
            ->and($user->designation?->name)->toBe($designationName);
    }

    expect(Designation::query()->where('code', 'QA_REV')->exists())->toBeTrue();
});

it('keeps QA batch release independent from executor and supervisor', function (): void {
    config()->set('modules.enabled', ['dms']);

    $this->seed(DatabaseSeeder::class);

    $reviewer = User::query()->where('email', 'QaReviewer@example.com')->firstOrFail();
    $executor = User::query()->where('email', 'RecordExecutor@example.com')->firstOrFail();
    $supervisor = User::query()->where('email', 'ProductionSupervisor@example.com')->firstOrFail();

    expect($reviewer->id)
        ->not->toBe($executor->id)
        ->and($reviewer->id)->not->toBe($supervisor->id)
        ->and($executor->id)->not->toBe($supervisor->id)
        ->and($reviewer->can('Approve:DocumentExecution'))->toBeTrue()
        ->and($reviewer->can('Review:DocumentExecution'))->toBeTrue()
        ->and($reviewer->can('Update:DocumentExecution'))->toBeFalse()
        ->and($executor->can('Update:DocumentExecution'))->toBeTrue()
        ->and($executor->can('Submit:DocumentExecution'))->toBeTrue()
        ->and($executor->can('Approve:DocumentExecution'))->toBeFalse()
        ->and($executor->can('Review:DocumentExecution'))->toBeFalse()
        ->and($supervisor->can('Review:DocumentExecution'))->toBeTrue()
        ->and($supervisor->can('Approve:DocumentExecution'))->toBeFalse()
        ->and($supervisor->can('Update:DocumentExecution'))->toBeFalse();
});

it('updates existing DMS demo users with QA department and designations when reseeded', function (): void {
    config()->set('modules.enabled', ['dms']);

    $this->seed(DatabaseSeeder::class);

    $reviewer = User::query()->where('email', 'QaReviewer@example.com')->firstOrFail();
    $reviewer->forceFill([
        'department_id' => null,
        'designation_id' => null,
    ])->save();

    $this->seed(DatabaseSeeder::class);

    $reviewer->refresh();

    expect($reviewer->department?->code)->toBe('QA')
        ->and($reviewer->designation?->code)->toBe('QA_REV')
        ->and($reviewer->hasRole('qa reviewer'))->toBeTrue();
});

it('removes stale grants from least-privilege DMS roles when reseeded', function (): void {
    config()->set('modules.enabled', ['dms']);

    $this->seed(DatabaseSeeder::class);

    $executorRole = Role::findByName('gmp record executor', 'web');
    $executorRole->givePermissionTo('Approve:DocumentExecution');

    expect($executorRole->hasPermissionTo('Approve:DocumentExecution'))->toBeTrue();

    $this->seed(DatabaseSeeder::class);

    expect($executorRole->refresh()->hasPermissionTo('Approve:DocumentExecution'))->toBeFalse();
});

it('adds AI permissions without changing DMS role grants for a DMS and AI installation', function (): void {
    config()->set('modules.enabled', ['dms', 'ai']);

    $this->seed(DatabaseSeeder::class);

    $expectedAdministratorPermissions = [
        ...CoreModuleSeeder::PERMISSIONS,
        ...DmsModuleSeeder::PERMISSIONS,
        ...AiModuleSeeder::PERMISSIONS,
    ];

    expect(Permission::query()->pluck('name')->all())
        ->toHaveCount(count($expectedAdministratorPermissions))
        ->toEqualCanonicalizing($expectedAdministratorPermissions)
        ->and(Role::findByName('sop administrator', 'web')->permissions->pluck('name')->all())
        ->toEqualCanonicalizing($expectedAdministratorPermissions)
        ->and(Role::findByName('sop maker', 'web')->hasPermissionTo('View:AiExecution'))
        ->toBeFalse();
});

it('adds change control permissions only for a QMS installation', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->seed(DatabaseSeeder::class);

    $expectedAdministratorPermissions = [
        ...CoreModuleSeeder::PERMISSIONS,
        ...DmsModuleSeeder::PERMISSIONS,
        ...QmsModuleSeeder::PERMISSIONS,
    ];

    expect(Permission::query()->pluck('name')->all())
        ->toHaveCount(count($expectedAdministratorPermissions))
        ->toEqualCanonicalizing($expectedAdministratorPermissions)
        ->and(Role::findByName('sop administrator', 'web')->permissions->pluck('name')->all())
        ->toEqualCanonicalizing($expectedAdministratorPermissions)
        ->and(Permission::query()->whereIn('name', AiModuleSeeder::PERMISSIONS)->exists())
        ->toBeFalse();
});

it('is idempotent when a QMS installation is seeded repeatedly', function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $expectedAdministratorPermissions = [
        ...CoreModuleSeeder::PERMISSIONS,
        ...DmsModuleSeeder::PERMISSIONS,
        ...QmsModuleSeeder::PERMISSIONS,
    ];

    expect(Permission::query()->pluck('name')->all())
        ->toHaveCount(count($expectedAdministratorPermissions))
        ->toEqualCanonicalizing($expectedAdministratorPermissions)
        ->and(Role::findByName('sop administrator', 'web')->permissions->pluck('name')->all())
        ->toHaveCount(count($expectedAdministratorPermissions))
        ->toEqualCanonicalizing($expectedAdministratorPermissions);
});

it('does not create the development bootstrap administrator in production', function (): void {
    $originalEnvironment = app()->environment();

    app()->detectEnvironment(fn (): string => 'production');

    try {
        config()->set('modules.enabled', ['dms', 'qms']);

        app(DatabaseSeeder::class)->run();

        expect(User::query()->where('email', 'admin@example.com')->exists())
            ->toBeFalse()
            ->and(Permission::query()->whereIn('name', QmsModuleSeeder::PERMISSIONS)->count())
            ->toBe(count(QmsModuleSeeder::PERMISSIONS));
    } finally {
        app()->detectEnvironment(fn (): string => $originalEnvironment);
    }
});
