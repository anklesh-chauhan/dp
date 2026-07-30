<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CoreModuleSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'ViewAny:Role',
        'View:Role',
        'Create:Role',
        'Update:Role',
        'Delete:Role',
        'DeleteAny:Role',
        'ForceDelete:Role',
        'ForceDeleteAny:Role',
        'Restore:Role',
        'RestoreAny:Role',
        'Replicate:Role',
        'Reorder:Role',
        'ViewAny:User',
        'View:User',
        'Create:User',
        'Update:User',
        'Delete:User',
        'DeleteAny:User',
        'ForceDelete:User',
        'ForceDeleteAny:User',
        'Restore:User',
        'RestoreAny:User',
        'Replicate:User',
        'Reorder:User',
        'ViewAny:ProductLicense',
        'View:ProductLicense',
        'ViewAny:Organization',
        'View:Organization',
        'Create:Organization',
        'Update:Organization',
        'Delete:Organization',
        'DeleteAny:Organization',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('sop administrator', 'web')
            ->givePermissionTo(self::PERMISSIONS);
    }
}
