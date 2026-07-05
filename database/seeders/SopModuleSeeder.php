<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApprovalStepType;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\SopWorkflow;
use App\Models\TemplateStatus;
use App\Models\VariableDataType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SopModuleSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $permissions = [
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

        'Approve:SopTemplate',

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
    ];

    public function run(): void
    {
        $this->call(LookupTableSeeder::class);

        $qa = Department::query()->firstOrCreate(['code' => 'QA'], ['name' => 'Quality Assurance']);
        $production = Department::query()->firstOrCreate(['code' => 'PROD'], ['name' => 'Production']);

        $category = DocumentCategory::query()->firstOrCreate(['code' => 'GMP'], ['name' => 'Good Manufacturing Practice']);
        $documentType = DocumentType::query()->firstOrCreate(['code' => DocumentType::SOP], ['name' => 'Standard Operating Procedure', 'requires_sop_reference' => false, 'is_issuable' => false]);
        $logType = DocumentType::query()->firstOrCreate(['code' => DocumentType::LOG], ['name' => 'Log Document', 'requires_sop_reference' => true, 'is_issuable' => true]);
        $bmrType = DocumentType::query()->firstOrCreate(['code' => DocumentType::BATCH_RECORD], ['name' => 'Batch Manufacturing Record', 'requires_sop_reference' => true, 'is_issuable' => true]);
        $formType = DocumentType::query()->firstOrCreate(['code' => DocumentType::FORM], ['name' => 'Controlled Form', 'requires_sop_reference' => true, 'is_issuable' => true]);

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('sop administrator', 'web');
        $adminRole->syncPermissions($this->permissions);

        $makerRole = Role::findOrCreate('sop maker', 'web');
        $makerRole->givePermissionTo([
            'ViewAny:SopDocument',
            'View:SopDocument',
            'Create:SopDocument',
            'Update:SopDocument',
            'Submit:SopDocument',
        ]);

        $checkerRole = Role::findOrCreate('sop checker', 'web');
        $checkerRole->givePermissionTo([
            'ViewAny:SopDocument',
            'View:SopDocument',
            'Approve:SopDocument',
            'ViewAny:SopApproval',
            'View:SopApproval',
            'Approve:SopApproval',
        ]);

        $approverRole = Role::findOrCreate('sop approver', 'web');
        $approverRole->givePermissionTo([
            'ViewAny:SopDocument',
            'View:SopDocument',
            'Approve:SopDocument',
            'ViewAny:SopApproval',
            'View:SopApproval',
            'Approve:SopApproval',
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
        ]);

        $documentControllerRole = Role::findOrCreate('document controller', 'web');
        $documentControllerRole->givePermissionTo([
            'ViewAny:LogDocument',
            'View:LogDocument',
            'ViewAny:DocumentIssuance',
            'View:DocumentIssuance',
            'Issue:DocumentIssuance',
            'Recall:DocumentIssuance',
            'Destroy:DocumentIssuance',
            'ViewAny:SopDocument',
            'View:SopDocument',
        ]);

        $reviewerRole = Role::findOrCreate('qa reviewer', 'web');
        $reviewerRole->givePermissionTo([
            'ViewAny:SopDocument',
            'View:SopDocument',
            'Approve:SopDocument',
            'ViewAny:SopApproval',
            'View:SopApproval',
            'Approve:SopApproval',
        ]);

        $publishedStatusId = TemplateStatus::idFor(TemplateStatus::PUBLISHED);

        $template = SopTemplate::query()->firstOrCreate([
            'code' => 'TPL-SOP-GMP',
        ], [
            'name' => 'GMP SOP Template',
            'description' => 'Baseline SOP template for GMP controlled procedures.',
            'department_id' => $qa->id,
            'category_id' => $category->id,
            'document_type_id' => $documentType->id,
            'template_status_id' => $publishedStatusId,
            'current_version' => 1,
        ]);

        $version = SopTemplateVersion::query()->firstOrCreate([
            'sop_template_id' => $template->id,
            'version' => 1,
        ], [
            'content_json' => [],
            'effective_date' => now()->toDateString(),
            'change_reason' => 'Initial seeded version',
            'template_status_id' => $publishedStatusId,
        ]);

        foreach (['Purpose', 'Scope', 'Responsibility', 'Procedure', 'Safety', 'References', 'Revision History'] as $order => $title) {
            $version->sections()->firstOrCreate([
                'section_order' => $order + 1,
            ], [
                'title' => $title,
                'section_type' => 'rich_text',
                'content' => "<p>{$title} for {{department}} document {{document_number}}.</p>",
                'is_required' => true,
            ]);
        }

        foreach (['department', 'prepared_by', 'approved_by', 'equipment', 'effective_date', 'review_date', 'document_number'] as $name) {
            $version->variables()->firstOrCreate([
                'name' => $name,
            ], [
                'label' => str($name)->replace('_', ' ')->title()->toString(),
                'variable_data_type_id' => VariableDataType::idFor(VariableDataType::TEXT),
                'required' => in_array($name, ['department', 'document_number'], true),
            ]);
        }

        $globalWorkflow = SopWorkflow::query()->firstOrCreate([
            'name' => 'Default SOP Approval Workflow',
        ], [
            'description' => 'Checker, QA review, and approver release for all departments.',
            'is_active' => true,
            'department_id' => null,
        ]);

        foreach ([
            1 => [ApprovalStepType::CHECKER, $checkerRole, null],
            2 => [ApprovalStepType::QA_REVIEW, $checkerRole, $qa->id],
            3 => [ApprovalStepType::APPROVER, $approverRole, null],
        ] as $stepNo => [$approvalType, $role, $departmentId]) {
            $globalWorkflow->steps()->firstOrCreate([
                'step_no' => $stepNo,
            ], [
                'role_id' => $role->id,
                'approval_step_type_id' => ApprovalStepType::idFor($approvalType),
                'department_id' => $departmentId,
                'is_mandatory' => true,
            ]);
        }

        $qaWorkflow = SopWorkflow::query()->firstOrCreate([
            'name' => 'QA Department Approval Workflow',
        ], [
            'description' => 'Department-specific maker-checker-approver flow for Quality Assurance.',
            'is_active' => true,
            'department_id' => $qa->id,
        ]);

        foreach ([
            1 => [ApprovalStepType::CHECKER, $checkerRole, null],
            2 => [ApprovalStepType::APPROVER, $approverRole, null],
        ] as $stepNo => [$approvalType, $role, $departmentId]) {
            $qaWorkflow->steps()->firstOrCreate([
                'step_no' => $stepNo,
            ], [
                'role_id' => $role->id,
                'approval_step_type_id' => ApprovalStepType::idFor($approvalType),
                'department_id' => $departmentId,
                'is_mandatory' => true,
            ]);
        }

        $prodWorkflow = SopWorkflow::query()->firstOrCreate([
            'name' => 'Production Department Approval Workflow',
        ], [
            'description' => 'Department-specific maker-checker-approver flow for Production.',
            'is_active' => true,
            'department_id' => $production->id,
        ]);

        foreach ([
            1 => [ApprovalStepType::CHECKER, $checkerRole, null],
            2 => [ApprovalStepType::QA_REVIEW, $checkerRole, $qa->id],
            3 => [ApprovalStepType::APPROVER, $approverRole, null],
        ] as $stepNo => [$approvalType, $role, $departmentId]) {
            $prodWorkflow->steps()->firstOrCreate([
                'step_no' => $stepNo,
            ], [
                'role_id' => $role->id,
                'approval_step_type_id' => ApprovalStepType::idFor($approvalType),
                'department_id' => $departmentId,
                'is_mandatory' => true,
            ]);
        }

        $logTemplate = SopTemplate::query()->firstOrCreate([
            'code' => 'TPL-LOG-GMP',
        ], [
            'name' => 'GMP Log Document Template',
            'description' => 'Controlled log document template requiring an effective SOP reference.',
            'department_id' => $qa->id,
            'category_id' => $category->id,
            'document_type_id' => $logType->id,
            'template_status_id' => $publishedStatusId,
            'current_version' => 1,
        ]);

        $logVersion = SopTemplateVersion::query()->firstOrCreate([
            'sop_template_id' => $logTemplate->id,
            'version' => 1,
        ], [
            'content_json' => [],
            'effective_date' => now()->toDateString(),
            'change_reason' => 'Initial log document template',
            'template_status_id' => $publishedStatusId,
        ]);

        foreach (['Purpose', 'Referenced SOP', 'Execution Log', 'Verification', 'Remarks'] as $order => $title) {
            $logVersion->sections()->firstOrCreate([
                'section_order' => $order + 1,
            ], [
                'title' => $title,
                'section_type' => 'rich_text',
                'content' => "<p>{$title} for {{document_number}} per approved SOP {{referenced_sop}}.</p>",
                'is_required' => true,
            ]);
        }

        foreach (['department', 'document_number', 'referenced_sop', 'batch_number', 'product_name'] as $name) {
            $logVersion->variables()->firstOrCreate([
                'name' => $name,
            ], [
                'label' => str($name)->replace('_', ' ')->title()->toString(),
                'variable_data_type_id' => match ($name) {
                    'department' => VariableDataType::idFor(VariableDataType::DEPARTMENT),
                    'referenced_sop' => VariableDataType::idFor(VariableDataType::SOP_REFERENCE),
                    default => VariableDataType::idFor(VariableDataType::TEXT),
                },
                'required' => in_array($name, ['department', 'document_number'], true),
            ]);
        }
    }
}
