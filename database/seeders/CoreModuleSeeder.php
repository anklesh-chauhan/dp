<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Designation;
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
        'ViewAny:Designation',
        'View:Designation',
        'Create:Designation',
        'Update:Designation',
        'Delete:Designation',
        'DeleteAny:Designation',
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

        $this->seedDesignations();
    }

    private function seedDesignations(): void
    {
        foreach ([
            ['code' => 'QA_MGR', 'name' => 'QA Manager'],
            ['code' => 'QA_OFF', 'name' => 'QA Officer'],
            ['code' => 'PROD_MGR', 'name' => 'Production Manager'],
            ['code' => 'PROD_SUP', 'name' => 'Production Supervisor'],
            ['code' => 'CHEMIST', 'name' => 'Chemist'],
            ['code' => 'PHARMACIST', 'name' => 'Pharmacist'],
            ['code' => 'DOC_CTRL', 'name' => 'Document Controller'],
        ] as $designation) {
            Designation::query()->firstOrCreate(
                ['code' => $designation['code']],
                ['name' => $designation['name']],
            );
        }
    }
}
