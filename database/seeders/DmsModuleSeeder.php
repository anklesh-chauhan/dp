<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DmsModuleSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'ViewAny:SopTemplate',
        'View:SopTemplate',
        'Create:SopTemplate',
        'Update:SopTemplate',
        'Delete:SopTemplate',
        'DeleteAny:SopTemplate',
        'ForceDelete:SopTemplate',
        'ForceDeleteAny:SopTemplate',
        'Restore:SopTemplate',
        'RestoreAny:SopTemplate',
        'Replicate:SopTemplate',
        'Reorder:SopTemplate',
        'Submit:SopTemplate',
        'Review:SopTemplate',
        'Decide:SopTemplateApproval',
        'ViewAny:SopTemplateApproval',
        'View:SopTemplateApproval',
        'ViewAny:SopDocument',
        'View:SopDocument',
        'Create:SopDocument',
        'Update:SopDocument',
        'Delete:SopDocument',
        'DeleteAny:SopDocument',
        'ForceDelete:SopDocument',
        'ForceDeleteAny:SopDocument',
        'Restore:SopDocument',
        'RestoreAny:SopDocument',
        'Replicate:SopDocument',
        'Reorder:SopDocument',
        'Approve:SopDocument',
        'Submit:SopDocument',
        'Archive:SopDocument',
        'MarkObsolete:SopDocument',
        'CompleteRetention:SopDocument',
        'Destroy:SopDocument',
        'Approve:SopTemplate',
        'Publish:SopTemplate',
        'Archive:SopTemplate',
        'MarkObsolete:SopTemplate',
        'CompleteRetention:SopTemplate',
        'Destroy:SopTemplate',
        'ViewAny:SopApproval',
        'View:SopApproval',
        'Approve:SopApproval',
        'ViewAny:SopWorkflow',
        'View:SopWorkflow',
        'Create:SopWorkflow',
        'Update:SopWorkflow',
        'Delete:SopWorkflow',
        'DeleteAny:SopWorkflow',
        'ForceDelete:SopWorkflow',
        'ForceDeleteAny:SopWorkflow',
        'Restore:SopWorkflow',
        'RestoreAny:SopWorkflow',
        'Replicate:SopWorkflow',
        'Reorder:SopWorkflow',
        'ViewAny:LogDocument',
        'View:LogDocument',
        'Create:LogDocument',
        'Update:LogDocument',
        'ViewAny:DocumentIssuance',
        'View:DocumentIssuance',
        'Issue:DocumentIssuance',
        'Recall:DocumentIssuance',
        'Destroy:DocumentIssuance',
        'ViewAny:DocumentStatus',
        'View:DocumentStatus',
        'Create:DocumentStatus',
        'Update:DocumentStatus',
        'Delete:DocumentStatus',
        'ViewAny:TemplateStatus',
        'View:TemplateStatus',
        'Create:TemplateStatus',
        'Update:TemplateStatus',
        'Delete:TemplateStatus',
        'ViewAny:VariableDataType',
        'View:VariableDataType',
        'Create:VariableDataType',
        'Update:VariableDataType',
        'Delete:VariableDataType',
        'ViewAny:ApprovalDecision',
        'View:ApprovalDecision',
        'Create:ApprovalDecision',
        'Update:ApprovalDecision',
        'Delete:ApprovalDecision',
        'ViewAny:IssuanceStatus',
        'View:IssuanceStatus',
        'Create:IssuanceStatus',
        'Update:IssuanceStatus',
        'Delete:IssuanceStatus',
        'ViewAny:ApprovalStepType',
        'View:ApprovalStepType',
        'Create:ApprovalStepType',
        'Update:ApprovalStepType',
        'Delete:ApprovalStepType',
        'ViewAny:SopRole',
        'View:SopRole',
        'Create:SopRole',
        'Update:SopRole',
        'Delete:SopRole',
        'ViewAny:DocumentType',
        'View:DocumentType',
        'Create:DocumentType',
        'Update:DocumentType',
        'Delete:DocumentType',
        'ViewAny:NumberSeries',
        'View:NumberSeries',
        'Update:NumberSeries',
        'View:ManageNumberSeriesSettings',
        'ViewAny:KnowledgeGuide',
        'View:KnowledgeGuide',
        'Create:KnowledgeGuide',
        'Update:KnowledgeGuide',
        'Delete:KnowledgeGuide',
        'DeleteAny:KnowledgeGuide',
        'ForceDelete:KnowledgeGuide',
        'ForceDeleteAny:KnowledgeGuide',
        'Restore:KnowledgeGuide',
        'RestoreAny:KnowledgeGuide',
        'Replicate:KnowledgeGuide',
        'Reorder:KnowledgeGuide',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::findOrCreate('sop administrator', 'web');
        $adminRole->syncPermissions([
            ...CoreModuleSeeder::PERMISSIONS,
            ...self::PERMISSIONS,
        ]);

        $makerRole = Role::findOrCreate('sop maker', 'web');
        $makerRole->givePermissionTo([
            'ViewAny:SopTemplate',
            'View:SopTemplate',
            'Create:SopTemplate',
            'Update:SopTemplate',
            'Submit:SopTemplate',
            'ViewAny:SopDocument',
            'View:SopDocument',
            'Create:SopDocument',
            'Update:SopDocument',
            'Submit:SopDocument',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $checkerRole = Role::findOrCreate('sop checker', 'web');
        $checkerRole->givePermissionTo([
            'ViewAny:SopTemplate',
            'View:SopTemplate',
            'Decide:SopTemplateApproval',
            'ViewAny:SopTemplateApproval',
            'View:SopTemplateApproval',
            'ViewAny:SopDocument',
            'View:SopDocument',
            'Approve:SopDocument',
            'ViewAny:SopApproval',
            'View:SopApproval',
            'Approve:SopApproval',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $approverRole = Role::findOrCreate('sop approver', 'web');
        $approverRole->givePermissionTo([
            'ViewAny:SopTemplate',
            'View:SopTemplate',
            'Approve:SopTemplate',
            'Decide:SopTemplateApproval',
            'ViewAny:SopTemplateApproval',
            'View:SopTemplateApproval',
            'ViewAny:SopDocument',
            'View:SopDocument',
            'Approve:SopDocument',
            'ViewAny:SopApproval',
            'View:SopApproval',
            'Approve:SopApproval',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $logMakerRole = Role::findOrCreate('log maker', 'web');
        $logMakerRole->givePermissionTo([
            'ViewAny:LogDocument',
            'View:LogDocument',
            'Create:LogDocument',
            'Update:LogDocument',
            'Submit:SopDocument',
            'ViewAny:SopDocument',
            'View:SopDocument',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $documentControllerRole = Role::findOrCreate('document controller', 'web');
        $documentControllerRole->givePermissionTo([
            'ViewAny:SopTemplate',
            'View:SopTemplate',
            'Publish:SopTemplate',
            'ViewAny:LogDocument',
            'View:LogDocument',
            'ViewAny:DocumentIssuance',
            'View:DocumentIssuance',
            'Issue:DocumentIssuance',
            'Recall:DocumentIssuance',
            'Destroy:DocumentIssuance',
            'ViewAny:SopDocument',
            'View:SopDocument',
            'MarkObsolete:SopDocument',
            'Archive:SopDocument',
            'CompleteRetention:SopDocument',
            'Destroy:SopDocument',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $reviewerRole = Role::findOrCreate('qa reviewer', 'web');
        $reviewerRole->givePermissionTo([
            'ViewAny:SopTemplate',
            'View:SopTemplate',
            'Review:SopTemplate',
            'Decide:SopTemplateApproval',
            'ViewAny:SopTemplateApproval',
            'View:SopTemplateApproval',
            'ViewAny:SopDocument',
            'View:SopDocument',
            'Approve:SopDocument',
            'ViewAny:SopApproval',
            'View:SopApproval',
            'Approve:SopApproval',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $this->call(SopModuleSeeder::class);
    }
}
