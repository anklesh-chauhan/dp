<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ApprovalStepType;
use App\Enums\ControlledDocumentTypeCode;
use App\Enums\TemplateStatus;
use App\Enums\VariableDataType;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\SopWorkflow;
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

        // Role
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

        // User
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
    ];

    public function run(): void
    {
        $qa = Department::query()->firstOrCreate(['code' => 'QA'], ['name' => 'Quality Assurance']);
        $production = Department::query()->firstOrCreate(['code' => 'PROD'], ['name' => 'Production']);

        $category = DocumentCategory::query()->firstOrCreate(['code' => 'GMP'], ['name' => 'Good Manufacturing Practice']);
        $documentType = DocumentType::query()->firstOrCreate(['code' => 'SOP'], ['name' => 'Standard Operating Procedure']);
        $logType = DocumentType::query()->firstOrCreate(['code' => ControlledDocumentTypeCode::Log->value], ['name' => 'Log Document']);
        $bmrType = DocumentType::query()->firstOrCreate(['code' => ControlledDocumentTypeCode::BatchRecord->value], ['name' => 'Batch Manufacturing Record']);
        $formType = DocumentType::query()->firstOrCreate(['code' => ControlledDocumentTypeCode::Form->value], ['name' => 'Controlled Form']);

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

        $template = SopTemplate::query()->firstOrCreate([
            'code' => 'TPL-SOP-GMP',
        ], [
            'name' => 'GMP SOP Template',
            'description' => 'Baseline SOP template for GMP controlled procedures.',
            'department_id' => $qa->id,
            'category_id' => $category->id,
            'document_type_id' => $documentType->id,
            'status' => TemplateStatus::Published,
            'current_version' => 1,
        ]);

        $version = SopTemplateVersion::query()->firstOrCreate([
            'sop_template_id' => $template->id,
            'version' => 1,
        ], [
            'content_json' => [],
            'effective_date' => now()->toDateString(),
            'change_reason' => 'Initial seeded version',
            'status' => TemplateStatus::Published,
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
                'datatype' => VariableDataType::Text,
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
            1 => [ApprovalStepType::Checker, $checkerRole],
            2 => [ApprovalStepType::QAReview, $checkerRole],
            3 => [ApprovalStepType::Approver, $approverRole],
        ] as $stepNo => [$approvalType, $role]) {
            $globalWorkflow->steps()->firstOrCreate([
                'step_no' => $stepNo,
            ], [
                'role_id' => $role->id,
                'approval_type' => $approvalType,
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
            1 => [ApprovalStepType::Checker, $checkerRole],
            2 => [ApprovalStepType::Approver, $approverRole],
        ] as $stepNo => [$approvalType, $role]) {
            $qaWorkflow->steps()->firstOrCreate([
                'step_no' => $stepNo,
            ], [
                'role_id' => $role->id,
                'approval_type' => $approvalType,
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
            1 => [ApprovalStepType::Checker, $checkerRole],
            2 => [ApprovalStepType::QAReview, $checkerRole],
            3 => [ApprovalStepType::Approver, $approverRole],
        ] as $stepNo => [$approvalType, $role]) {
            $prodWorkflow->steps()->firstOrCreate([
                'step_no' => $stepNo,
            ], [
                'role_id' => $role->id,
                'approval_type' => $approvalType,
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
            'status' => TemplateStatus::Published,
            'current_version' => 1,
        ]);

        $logVersion = SopTemplateVersion::query()->firstOrCreate([
            'sop_template_id' => $logTemplate->id,
            'version' => 1,
        ], [
            'content_json' => [],
            'effective_date' => now()->toDateString(),
            'change_reason' => 'Initial log document template',
            'status' => TemplateStatus::Published,
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
                'datatype' => match ($name) {
                    'department' => VariableDataType::Department,
                    'referenced_sop' => VariableDataType::SopReference,
                    default => VariableDataType::Text,
                },
                'required' => in_array($name, ['department', 'document_number'], true),
            ]);
        }
    }
}
