<?php

declare(strict_types=1);

namespace Database\Seeders;

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
    ];

    public function run(): void
    {
        $qa = Department::query()->firstOrCreate(['code' => 'QA'], ['name' => 'Quality Assurance']);
        Department::query()->firstOrCreate(['code' => 'PROD'], ['name' => 'Production']);

        $category = DocumentCategory::query()->firstOrCreate(['code' => 'GMP'], ['name' => 'Good Manufacturing Practice']);
        $documentType = DocumentType::query()->firstOrCreate(['code' => 'SOP'], ['name' => 'Standard Operating Procedure']);

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $adminRole = Role::findOrCreate('sop administrator', 'web');
        $adminRole->syncPermissions($this->permissions);

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

        $workflow = SopWorkflow::query()->firstOrCreate([
            'name' => 'Default SOP Approval Workflow',
        ], [
            'description' => 'Review, QA review, approval, effective release.',
            'is_active' => true,
        ]);

        foreach ([1 => 'review', 2 => 'qa_review', 3 => 'approval'] as $stepNo => $approvalType) {
            $workflow->steps()->firstOrCreate([
                'step_no' => $stepNo,
            ], [
                'role_id' => $reviewerRole->id,
                'approval_type' => $approvalType,
                'is_mandatory' => true,
            ]);
        }
    }
}
