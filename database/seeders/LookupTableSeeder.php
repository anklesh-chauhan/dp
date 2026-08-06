<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApprovalDecision;
use App\Models\ApprovalStepType;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\IssuanceStatus;
use App\Models\SopRole;
use App\Models\TemplateStatus;
use App\Models\VariableDataType;
use Illuminate\Database\Seeder;

class LookupTableSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLookup(DocumentStatus::class, [
            [DocumentStatus::DRAFT, 'Draft', 1],
            [DocumentStatus::UNDER_REVIEW, 'Under Review', 2],
            [DocumentStatus::APPROVED, 'Approved', 3],
            [DocumentStatus::EFFECTIVE, 'Effective', 4],
            [DocumentStatus::SUPERSEDED, 'Superseded', 5],
            [DocumentStatus::OBSOLETE, 'Obsolete', 6],
            [DocumentStatus::ARCHIVED, 'Archived', 7],
            [DocumentStatus::RETENTION_COMPLETED, 'Retention Completed', 8],
            [DocumentStatus::DESTROYED, 'Destroyed', 9],
            [DocumentStatus::REJECTED, 'Rejected', 10],
        ]);

        $this->seedLookup(TemplateStatus::class, [
            [TemplateStatus::DRAFT, 'Draft', 1],
            [TemplateStatus::PUBLISHED, 'Published', 2],
            [TemplateStatus::OBSOLETE, 'Obsolete', 3],
            [TemplateStatus::ARCHIVED, 'Archived', 4],
            [TemplateStatus::RETENTION_COMPLETED, 'Retention Completed', 5],
            [TemplateStatus::DESTROYED, 'Destroyed', 6],
        ]);

        $this->seedLookup(VariableDataType::class, [
            [VariableDataType::TEXT, 'Text', 1],
            [VariableDataType::LONG_TEXT, 'Long Text', 2],
            [VariableDataType::TEXTAREA, 'Textarea (Legacy)', 3],
            [VariableDataType::RICH_TEXT, 'Rich Text', 4],
            [VariableDataType::INTEGER, 'Integer', 5],
            [VariableDataType::DECIMAL, 'Decimal', 6],
            [VariableDataType::NUMBER, 'Number (Legacy)', 7],
            [VariableDataType::CURRENCY, 'Currency', 8],
            [VariableDataType::PERCENTAGE, 'Percentage', 9],
            [VariableDataType::DATE, 'Date', 10],
            [VariableDataType::DATETIME, 'Date & Time', 11],
            [VariableDataType::TIME, 'Time', 12],
            [VariableDataType::BOOLEAN, 'Boolean', 13],
            [VariableDataType::CHECKBOX, 'Checkbox', 14],
            [VariableDataType::SELECT, 'Select', 15],
            [VariableDataType::MULTI_SELECT, 'Multi Select', 16],
            [VariableDataType::RADIO, 'Radio', 17],
            [VariableDataType::USER, 'User', 18],
            [VariableDataType::EMPLOYEE, 'Employee', 19],
            [VariableDataType::DEPARTMENT, 'Department', 20],
            [VariableDataType::DESIGNATION, 'Designation', 21],
            [VariableDataType::SOP_REFERENCE, 'SOP Reference', 22],
            [VariableDataType::CONTROLLED_DOCUMENT, 'Controlled Document', 23],
            [VariableDataType::DOCUMENT_NUMBER, 'Document Number', 24],
            [VariableDataType::FILE, 'File', 25],
            [VariableDataType::IMAGE, 'Image', 26],
            [VariableDataType::URL, 'URL', 27],
            [VariableDataType::EMAIL, 'Email', 28],
            [VariableDataType::PHONE, 'Phone', 29],
        ]);

        $this->seedLookup(ApprovalDecision::class, [
            [ApprovalDecision::PENDING, 'Pending', 1],
            [ApprovalDecision::APPROVED, 'Approved', 2],
            [ApprovalDecision::REJECTED, 'Rejected', 3],
            [ApprovalDecision::RETURNED, 'Returned', 4],
        ]);

        $this->seedLookup(IssuanceStatus::class, [
            [IssuanceStatus::ACTIVE, 'Active', 1],
            [IssuanceStatus::RECALLED, 'Recalled', 2],
            [IssuanceStatus::DESTROYED, 'Destroyed', 3],
        ]);

        $this->seedLookup(ApprovalStepType::class, [
            [ApprovalStepType::CHECKER, 'Checker', 1],
            [ApprovalStepType::REVIEW, 'Checker', 2],
            [ApprovalStepType::QA_REVIEW, 'QA Review', 3],
            [ApprovalStepType::APPROVER, 'Approver', 4],
            [ApprovalStepType::APPROVAL, 'Approver', 5],
        ]);

        $this->seedLookup(SopRole::class, [
            [SopRole::ADMINISTRATOR, 'SOP Administrator', 1],
            [SopRole::MAKER, 'SOP Maker', 2],
            [SopRole::CHECKER, 'SOP Checker', 3],
            [SopRole::APPROVER, 'SOP Approver', 4],
        ]);

        $this->seedDocumentTypes();
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<int, array{0: string, 1: string, 2: int}>  $rows
     */
    private function seedLookup(string $modelClass, array $rows): void
    {
        foreach ($rows as [$code, $name, $sortOrder]) {
            $modelClass::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'sort_order' => $sortOrder],
            );
        }
    }

    private function seedDocumentTypes(): void
    {
        $types = [
            ['code' => 'SOP', 'name' => 'Standard Operating Procedure', 'description' => 'Controlled procedure with managed revisions and approvals.', 'format_profile' => 'text_document', 'requires_sop_reference' => false, 'is_issuable' => false],
            ['code' => 'POLICY', 'name' => 'Policy', 'description' => 'Quality or operational policy.', 'format_profile' => 'text_document', 'requires_sop_reference' => false, 'is_issuable' => false],
            ['code' => 'MANUAL', 'name' => 'Manual', 'description' => 'Controlled manual containing multiple related topics.', 'format_profile' => 'text_document', 'requires_sop_reference' => false, 'is_issuable' => false],
            ['code' => 'LOG', 'name' => 'Log Document', 'description' => 'Repeated-entry record such as a temperature or equipment log.', 'format_profile' => 'repeating_log', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'BMR', 'name' => 'Batch Manufacturing Record', 'description' => 'Controlled batch manufacturing execution record.', 'format_profile' => 'controlled_form', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'BPR', 'name' => 'Batch Packaging Record', 'description' => 'Controlled batch packaging execution record.', 'format_profile' => 'controlled_form', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'FORM', 'name' => 'Controlled Form', 'description' => 'Blank controlled form used to capture GMP records.', 'format_profile' => 'controlled_form', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'CHECKLIST', 'name' => 'Checklist', 'description' => 'Short controlled checklist with pass, fail, or not-applicable entries.', 'format_profile' => 'checklist', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'REPORT', 'name' => 'Report', 'description' => 'Structured quality, validation, or investigation report.', 'format_profile' => 'structured_table', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'PROTOCOL', 'name' => 'Protocol', 'description' => 'Controlled protocol for validation, qualification, or testing.', 'format_profile' => 'structured_table', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'SPEC', 'name' => 'Specification', 'description' => 'Structured acceptance criteria and specification record.', 'format_profile' => 'structured_table', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'VALIDATION', 'name' => 'Validation', 'description' => 'Validation or qualification document.', 'format_profile' => 'structured_table', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'ANNEXURE', 'name' => 'Annexure', 'description' => 'Controlled supporting attachment to a document.', 'format_profile' => 'attachment_package', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'CHANGE_CONTROL', 'name' => 'Change Control', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'CAPA', 'name' => 'Corrective and Preventive Action', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'DEV', 'name' => 'Deviation', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'INCIDENT', 'name' => 'Incident', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'AUDIT', 'name' => 'Audit', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'INSPECTION', 'name' => 'Inspection', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'TEST', 'name' => 'Test', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'TRAINING', 'name' => 'Training', 'requires_sop_reference' => true, 'is_issuable' => true],
            ['code' => 'OTHER', 'name' => 'Other', 'requires_sop_reference' => true, 'is_issuable' => true],
        ];

        foreach ($types as $type) {
            DocumentType::query()->updateOrCreate(
                ['code' => $type['code']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'] ?? null,
                    'format_profile' => $type['format_profile'] ?? 'text_document',
                    'requires_sop_reference' => $type['requires_sop_reference'],
                    'is_issuable' => $type['is_issuable'],
                ],
            );
        }
    }
}
