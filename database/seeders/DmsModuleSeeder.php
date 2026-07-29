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
        'ViewAny:DocumentTemplate',
        'View:DocumentTemplate',
        'Create:DocumentTemplate',
        'Update:DocumentTemplate',
        'Delete:DocumentTemplate',
        'DeleteAny:DocumentTemplate',
        'ForceDelete:DocumentTemplate',
        'ForceDeleteAny:DocumentTemplate',
        'Restore:DocumentTemplate',
        'RestoreAny:DocumentTemplate',
        'Replicate:DocumentTemplate',
        'Reorder:DocumentTemplate',
        'Submit:DocumentTemplate',
        'Review:DocumentTemplate',
        'Decide:DocumentTemplateApproval',
        'ViewAny:DocumentTemplateApproval',
        'View:DocumentTemplateApproval',
        'ViewAny:ControlledDocument',
        'View:ControlledDocument',
        'Create:ControlledDocument',
        'Update:ControlledDocument',
        'Delete:ControlledDocument',
        'DeleteAny:ControlledDocument',
        'ForceDelete:ControlledDocument',
        'ForceDeleteAny:ControlledDocument',
        'Restore:ControlledDocument',
        'RestoreAny:ControlledDocument',
        'Replicate:ControlledDocument',
        'Reorder:ControlledDocument',
        'Approve:ControlledDocument',
        'Submit:ControlledDocument',
        'Archive:ControlledDocument',
        'MarkObsolete:ControlledDocument',
        'CompleteRetention:ControlledDocument',
        'Destroy:ControlledDocument',
        'Approve:DocumentTemplate',
        'Publish:DocumentTemplate',
        'Archive:DocumentTemplate',
        'MarkObsolete:DocumentTemplate',
        'CompleteRetention:DocumentTemplate',
        'Destroy:DocumentTemplate',
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
            'ViewAny:DocumentTemplate',
            'View:DocumentTemplate',
            'Create:DocumentTemplate',
            'Update:DocumentTemplate',
            'Submit:DocumentTemplate',
            'ViewAny:ControlledDocument',
            'View:ControlledDocument',
            'Create:ControlledDocument',
            'Update:ControlledDocument',
            'Submit:ControlledDocument',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $checkerRole = Role::findOrCreate('sop checker', 'web');
        $checkerRole->givePermissionTo([
            'ViewAny:DocumentTemplate',
            'View:DocumentTemplate',
            'Decide:DocumentTemplateApproval',
            'ViewAny:DocumentTemplateApproval',
            'View:DocumentTemplateApproval',
            'ViewAny:ControlledDocument',
            'View:ControlledDocument',
            'Approve:ControlledDocument',
            'ViewAny:SopApproval',
            'View:SopApproval',
            'Approve:SopApproval',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $approverRole = Role::findOrCreate('sop approver', 'web');
        $approverRole->givePermissionTo([
            'ViewAny:DocumentTemplate',
            'View:DocumentTemplate',
            'Approve:DocumentTemplate',
            'Decide:DocumentTemplateApproval',
            'ViewAny:DocumentTemplateApproval',
            'View:DocumentTemplateApproval',
            'ViewAny:ControlledDocument',
            'View:ControlledDocument',
            'Approve:ControlledDocument',
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
            'Submit:ControlledDocument',
            'ViewAny:ControlledDocument',
            'View:ControlledDocument',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $documentControllerRole = Role::findOrCreate('document controller', 'web');
        $documentControllerRole->givePermissionTo([
            'ViewAny:DocumentTemplate',
            'View:DocumentTemplate',
            'Publish:DocumentTemplate',
            'ViewAny:LogDocument',
            'View:LogDocument',
            'ViewAny:DocumentIssuance',
            'View:DocumentIssuance',
            'Issue:DocumentIssuance',
            'Recall:DocumentIssuance',
            'Destroy:DocumentIssuance',
            'ViewAny:ControlledDocument',
            'View:ControlledDocument',
            'MarkObsolete:ControlledDocument',
            'Archive:ControlledDocument',
            'CompleteRetention:ControlledDocument',
            'Destroy:ControlledDocument',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $reviewerRole = Role::findOrCreate('qa reviewer', 'web');
        $reviewerRole->givePermissionTo([
            'ViewAny:DocumentTemplate',
            'View:DocumentTemplate',
            'Review:DocumentTemplate',
            'Decide:DocumentTemplateApproval',
            'ViewAny:DocumentTemplateApproval',
            'View:DocumentTemplateApproval',
            'ViewAny:ControlledDocument',
            'View:ControlledDocument',
            'Approve:ControlledDocument',
            'ViewAny:SopApproval',
            'View:SopApproval',
            'Approve:SopApproval',
            'ViewAny:KnowledgeGuide',
            'View:KnowledgeGuide',
        ]);

        $this->call(SopModuleSeeder::class);
    }
}
