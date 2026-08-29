<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AiModuleSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'ViewAny:AiExecution',
        'View:AiExecution',
        'Use:AiAssistant',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('sop administrator', 'web')
            ->givePermissionTo(self::PERMISSIONS);

        foreach ([
            'sop maker',
            'sop checker',
            'sop approver',
            'document controller',
            'qa reviewer',
            'log maker',
            'gmp record executor',
            'production supervisor',
        ] as $roleName) {
            Role::findOrCreate($roleName, 'web')
                ->givePermissionTo('Use:AiAssistant');
        }
    }
}
