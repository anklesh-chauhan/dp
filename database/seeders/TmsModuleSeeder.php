<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class TmsModuleSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'View:MyTraining',
        'Complete:TrainingAssignment',
        'ViewAny:TrainingAssignment',
        'View:TrainingAssignment',
        'ViewAny:TrainingProgram',
        'View:TrainingProgram',
        'Create:TrainingProgram',
        'Update:TrainingProgram',
        'Assign:TrainingProgram',
        'Manage:TrainingProgram',
        'View:TrainingMatrix',
        'ViewAny:CompetencyCurriculum',
        'View:CompetencyCurriculum',
        'Create:CompetencyCurriculum',
        'Update:CompetencyCurriculum',
        'Assign:CompetencyCurriculum',
        'Manage:CompetencyCurriculum',
        'ViewAny:UserCompetency',
        'View:UserCompetency',
        'Create:UserCompetency',
        'Update:UserCompetency',
        'Assign:UserCompetency',
        'Verify:UserCompetency',
        'Manage:UserCompetency',
    ];

    /**
     * @var array<int, string>
     */
    public const TRAINEE_PERMISSIONS = [
        'View:MyTraining',
        'Complete:TrainingAssignment',
    ];

    /**
     * @var array<int, string>
     */
    public const COORDINATOR_PERMISSIONS = [
        'ViewAny:TrainingAssignment',
        'View:TrainingAssignment',
        'ViewAny:TrainingProgram',
        'View:TrainingProgram',
        'Create:TrainingProgram',
        'Update:TrainingProgram',
        'Assign:TrainingProgram',
        'View:TrainingMatrix',
        'ViewAny:CompetencyCurriculum',
        'View:CompetencyCurriculum',
        'Create:CompetencyCurriculum',
        'Update:CompetencyCurriculum',
        'Assign:CompetencyCurriculum',
        'ViewAny:UserCompetency',
        'View:UserCompetency',
        'Create:UserCompetency',
        'Update:UserCompetency',
        'Assign:UserCompetency',
        'Verify:UserCompetency',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('sop administrator', 'web')
            ->givePermissionTo(self::PERMISSIONS);

        Role::findOrCreate('document controller', 'web')
            ->givePermissionTo(self::COORDINATOR_PERMISSIONS);

        foreach ([
            'sop maker',
            'sop checker',
            'sop approver',
            'qa reviewer',
            'log maker',
            'gmp record executor',
            'production supervisor',
        ] as $roleName) {
            Role::findOrCreate($roleName, 'web')
                ->givePermissionTo(self::TRAINEE_PERMISSIONS);
        }

        $this->call(CompetencyCurriculumSeeder::class);
    }
}
