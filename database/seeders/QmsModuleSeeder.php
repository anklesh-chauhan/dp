<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class QmsModuleSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'ViewAny:ChangeControl',
        'View:ChangeControl',
        'Create:ChangeControl',
        'Update:ChangeControl',
        'Submit:ChangeControl',
        'Manage:ChangeControl',
        'Implement:ChangeControl',
        'Review:ChangeControl',
        'Approve:ChangeControl',
        'VerifyEffectiveness:ChangeControl',
        'Close:ChangeControl',
        'ViewAny:Deviation',
        'View:Deviation',
        'Create:Deviation',
        'Update:Deviation',
        'Submit:Deviation',
        'Investigate:Deviation',
        'Close:Deviation',
        'Manage:Deviation',
        'VerifyEffectiveness:Deviation',
        'ViewAny:Investigation',
        'View:Investigation',
        'Create:Investigation',
        'Update:Investigation',
        'Review:Investigation',
        'Complete:Investigation',
        'Manage:Investigation',
        'ViewAny:Capa',
        'View:Capa',
        'Create:Capa',
        'Update:Capa',
        'Implement:Capa',
        'VerifyEffectiveness:Capa',
        'Close:Capa',
        'Manage:Capa',
        'View:QualityAttachment',
        'Create:QualityAttachment',
        'Decide:QualityApproval',
        'ViewAny:Complaint',
        'View:Complaint',
        'Create:Complaint',
        'Update:Complaint',
        'Assess:Complaint',
        'Investigate:Complaint',
        'Respond:Complaint',
        'Close:Complaint',
        'Manage:Complaint',
        'ViewAny:InternalAudit',
        'View:InternalAudit',
        'Create:InternalAudit',
        'Update:InternalAudit',
        'Schedule:InternalAudit',
        'Conduct:InternalAudit',
        'Report:InternalAudit',
        'FollowUp:InternalAudit',
        'Close:InternalAudit',
        'Manage:InternalAudit',
        'ViewAny:AuditFinding',
        'View:AuditFinding',
        'Create:AuditFinding',
        'Update:AuditFinding',
        'Respond:AuditFinding',
        'Verify:AuditFinding',
        'Close:AuditFinding',
        'Manage:AuditFinding',
        'ViewAny:RiskAssessment',
        'View:RiskAssessment',
        'Create:RiskAssessment',
        'Update:RiskAssessment',
        'Review:RiskAssessment',
        'Approve:RiskAssessment',
        'Mitigate:RiskAssessment',
        'Monitor:RiskAssessment',
        'Close:RiskAssessment',
        'Manage:RiskAssessment',
        'ViewAny:SupplierQualification',
        'View:SupplierQualification',
        'Create:SupplierQualification',
        'Update:SupplierQualification',
        'Assess:SupplierQualification',
        'Audit:SupplierQualification',
        'Approve:SupplierQualification',
        'Suspend:SupplierQualification',
        'Disqualify:SupplierQualification',
        'Review:SupplierQualification',
        'Manage:SupplierQualification',
        'ViewAny:ManagementReview',
        'View:ManagementReview',
        'Create:ManagementReview',
        'Update:ManagementReview',
        'Schedule:ManagementReview',
        'Conduct:ManagementReview',
        'IssueMinutes:ManagementReview',
        'Approve:ManagementReview',
        'Complete:ManagementReview',
        'Manage:ManagementReview',
        'View:QualityMetrics',
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
