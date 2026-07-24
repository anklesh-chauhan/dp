<?php

declare(strict_types=1);

use Database\Seeders\AiModuleSeeder;
use Database\Seeders\CoreModuleSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DmsModuleSeeder;
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
        ->and(Role::findByName('sop maker', 'web')->hasPermissionTo('Create:SopDocument'))
        ->toBeTrue()
        ->and(Role::findByName('document controller', 'web')->hasPermissionTo('Issue:DocumentIssuance'))
        ->toBeTrue();
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
