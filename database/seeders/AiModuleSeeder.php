<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AiModuleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'ViewAny:AiExecution',
            'View:AiExecution',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('sop administrator', 'web')
            ->givePermissionTo($permissions);
    }
}
