<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApprovalStepType;
use App\Models\ControlledDocumentSectionItem;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentType;
use App\Models\RegulationTag;
use App\Models\SopWorkflow;
use App\Models\TemplateStatus;
use App\Models\User;
use App\Models\VariableDataType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SopModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LookupTableSeeder::class);
        $this->call(NumberSeriesSeeder::class);
        $this->call(KnowledgeGuideSeeder::class);

        $qa = Department::query()->firstOrCreate(['code' => 'QA'], ['name' => 'Quality Assurance']);
        $production = Department::query()->firstOrCreate(['code' => 'PROD'], ['name' => 'Production']);

        $categories = [
            ['code' => 'QMS', 'name' => 'Quality Management System (QMS) Core'],
            ['code' => 'FAC', 'name' => 'Facility & Equipment'],
            ['code' => 'MAT', 'name' => 'Materials & Warehouse'],
            ['code' => 'PRD', 'name' => 'Production / Manufacturing'],
            ['code' => 'QC',  'name' => 'Quality Control (QC) & Laboratory'],
            ['code' => 'VAL', 'name' => 'Validation & Qualification'],
            ['code' => 'CMP', 'name' => 'Compliance & Risk Management'],
            ['code' => 'TRN', 'name' => 'Training & Personnel'],
            ['code' => 'DST', 'name' => 'Distribution & Supply Chain'],
            ['code' => 'REG', 'name' => 'Regulatory & Product Licensing'],
        ];

        foreach ($categories as $category) {
            DocumentCategory::query()->firstOrCreate(
                ['code' => $category['code']],
                ['name' => $category['name']]
            );
        }

        $regulationTags = [
            ['code' => 'INDIA_DPCO', 'name' => 'India Drug Price Control Order (DPCO)'],
            ['code' => 'INDIA_DCGI', 'name' => 'India Drug Controller General of India (DCGI)'],
            ['code' => 'INDIA_FDA', 'name' => 'India Food and Drug Administration (FDA)'],
            ['code' => 'INDIA_CDSCO', 'name' => 'India Central Drugs Standard Control Organization (CDSCO)'],
            ['code' => 'INDIA_PFIA', 'name' => 'India Pharmacovigilance and Vigilance Authority (PFIA)'],
            ['code' => 'INDIA_NPPA', 'name' => 'India National Pharmaceutical Pricing Authority (NPPA)'],
            ['code' => 'INDIA_CCI', 'name' => 'India Competition Commission (CCI)'],
            ['code' => 'INDIA_CPCB', 'name' => 'India Central Pollution Control Board (CPCB)'],
            ['code' => 'GMP', 'name' => 'Good Manufacturing Practice (GMP)'],
            ['code' => 'GLP', 'name' => 'Good Laboratory Practice (GLP)'],
            ['code' => 'GCP', 'name' => 'Good Clinical Practice (GCP)'],
            ['code' => 'GDP', 'name' => 'Good Distribution Practice (GDP)'],
            ['code' => 'WHO_GMP', 'name' => 'World Health Organization Good Manufacturing Practice (WHO GMP)'],
            ['code' => 'FDA_GMP', 'name' => 'Food and Drug Administration Good Manufacturing Practice (FDA GMP)'],
            ['code' => 'EMA_GMP', 'name' => 'European Medicines Agency Good Manufacturing Practice (EMA GMP)'],
            ['code' => 'ICH_GMP', 'name' => 'International Conference on Harmonization Good Manufacturing Practice (ICH GMP)'],
            ['code' => 'US_FDA_210_211', 'name' => 'United States Food and Drug Administration Good Manufacturing Practice (US FDA 210 & 211)'],
            ['code' => 'ISO_GMP', 'name' => 'International Organization for Standardization Good Manufacturing Practice (ISO GMP)'],
        ];

        foreach ($regulationTags as $regulationTag) {
            RegulationTag::query()->firstOrCreate(
                ['code' => $regulationTag['code']],
                ['name' => $regulationTag['name']]
            );
        }

        // //////////////////////////////////////////////////////////////
        // //////////////  Document Types  //////////////////////////////
        // //////////////////////////////////////////////////////////////
        $categories = DocumentCategory::pluck('id', 'name')->toArray();
        $tags = RegulationTag::pluck('id', 'code')->toArray();

        // 2. Comprehensive Data Array for all 10 Categories
        $data = [
            [
                'category_name' => 'Quality Management System (QMS) Core',
                'documents' => [
                    ['name' => 'Quality Manual', 'code' => 'QM'],
                    ['name' => 'Quality Policy', 'code' => 'QP'],
                    ['name' => 'QMS Scope & Objectives', 'code' => 'QSO'],
                    ['name' => 'Document Control SOP', 'code' => 'DCSOP'],
                    ['name' => 'Record Control SOP', 'code' => 'RCSOP'],
                    ['name' => 'Change Control SOP', 'code' => 'CCSOP'],
                    ['name' => 'CAPA SOP', 'code' => 'CAPASOP'],
                    ['name' => 'Deviation Management SOP', 'code' => 'DMSOP'],
                    ['name' => 'Internal Audit SOP', 'code' => 'IASOP'],
                    ['name' => 'Management Review SOP', 'code' => 'MRSOP'],
                ],
                'regulations' => ['INDIA_DPCO', 'WHO_GMP', 'US_FDA_210_211'],
                'sub_tags' => [
                    ['code' => 'QMS_MAN', 'name' => 'Quality Management'],
                    ['code' => 'QMS_DOC', 'name' => 'Documentation & Records'],
                    ['code' => 'QMS_AUD', 'name' => 'Audits & Reviews'],
                    ['code' => 'QMS_QCU', 'name' => 'Quality Control Unit Responsibilities'],
                ],
            ],
            [
                'category_name' => 'Facility & Equipment',
                'documents' => [
                    ['name' => 'Facility Design & Maintenance SOP', 'code' => 'FDMSOP'],
                    ['name' => 'Utilities (Water, HVAC, Air) SOP', 'code' => 'UTLSOP'],
                    ['name' => 'Equipment Selection & Qualification Policy', 'code' => 'EQPL'],
                    ['name' => 'Equipment Qualification Plan (IQ/OQ/PQ)', 'code' => 'EQP'],
                    ['name' => 'Equipment Calibration SOP', 'code' => 'ECALSOP'],
                    ['name' => 'Equipment Maintenance SOP', 'code' => 'EMNTSOP'],
                    ['name' => 'Cleaning & Sanitation SOP (Facility)', 'code' => 'CSOP'],
                    ['name' => 'Preventive Maintenance Log', 'code' => 'PMLOG'],
                    ['name' => 'Calibration Log', 'code' => 'CALLOG'],
                ],
                'regulations' => ['WHO_GMP', 'US_FDA_210_211', 'INDIA_DPCO'],
                'sub_tags' => [
                    ['code' => 'FAC_PREM', 'name' => 'Premises & Plant Hygiene'],
                    ['code' => 'FAC_EQUIP', 'name' => 'Equipment Design & Calibration'],
                    ['code' => 'FAC_WTR', 'name' => 'Water & HVAC Systems'],
                    ['code' => 'FAC_MNT', 'name' => 'Maintenance & Cleaning'],
                ],
            ],
            [
                'category_name' => 'Materials & Warehouse',
                'documents' => [
                    ['name' => 'Material Procurement SOP', 'code' => 'MPRCSOP'],
                    ['name' => 'Vendor Qualification SOP', 'code' => 'VQALSOP'],
                    ['name' => 'Raw Material Receipt & Inspection SOP', 'code' => 'RMRISOP'],
                    ['name' => 'Sampling SOP (Materials)', 'code' => 'SAMPSOP'],
                    ['name' => 'Material Storage & Handling SOP', 'code' => 'MSHSOP'],
                    ['name' => 'Warehouse Temperature Monitoring SOP', 'code' => 'WTMSOP'],
                    ['name' => 'Expired / Rejected Material SOP', 'code' => 'ERMSOP'],
                    ['name' => 'Material Disposition SOP', 'code' => 'MDIPSOP'],
                    ['name' => 'Receiving Log', 'code' => 'RCVLOG'],
                    ['name' => 'Storage Condition Log', 'code' => 'STGLOG'],
                    ['name' => 'Material Disposition Record', 'code' => 'MDIPR'],
                ],
                'regulations' => ['WHO_GMP', 'US_FDA_210_211', 'INDIA_DPCO'],
                'sub_tags' => [
                    ['code' => 'MAT_CTRL', 'name' => 'Materials Control & Storage'],
                    ['code' => 'MAT_QUAR', 'name' => 'Quarantine & Labeling'],
                    ['code' => 'MAT_COMP', 'name' => 'Components, Containers & Closures'],
                    ['code' => 'MAT_TEST', 'name' => 'Purchase, Testing & Rejection'],
                ],
            ],
            [
                'category_name' => 'Production / Manufacturing',
                'documents' => [
                    ['name' => 'Master Production Instruction (MPI) Template', 'code' => 'MPIT'],
                    ['name' => 'Batch Production Record (BPR) Template', 'code' => 'BPRT'],
                    ['name' => 'Line Clearance SOP', 'code' => 'LCSOP'],
                    ['name' => 'In-process Control SOP', 'code' => 'IPCSOP'],
                    ['name' => 'Weighing & Dispensing SOP', 'code' => 'WDSOP'],
                    ['name' => 'Packaging SOP', 'code' => 'PKGSOP'],
                    ['name' => 'Line Changeover SOP', 'code' => 'LCOSOP'],
                    ['name' => 'Production Deviation Log', 'code' => 'PDLOG'],
                    ['name' => 'Batch Disposition Record', 'code' => 'BDRE'],
                    ['name' => 'Yield Calculation Record', 'code' => 'YCREC'],
                ],
                'regulations' => ['WHO_GMP', 'US_FDA_210_211', 'INDIA_DPCO'],
                'sub_tags' => [
                    ['code' => 'PRD_CTRL', 'name' => 'Production Process Control'],
                    ['code' => 'PRD_BCH', 'name' => 'Batch Records & Documentation'],
                    ['code' => 'PRD_IPC', 'name' => 'In-process Checks'],
                    ['code' => 'PRD_PKG', 'name' => 'Labeling & Packaging Controls'],
                ],
            ],
            [
                'category_name' => 'Quality Control (QC) & Laboratory',
                'documents' => [
                    ['name' => 'Laboratory Organization & Responsibilities SOP', 'code' => 'LORSOP'],
                    ['name' => 'Sample Collection SOP', 'code' => 'SCSOP'],
                    ['name' => 'Analytical Method Validation SOP', 'code' => 'AMVSOP'],
                    ['name' => 'Method Transfer SOP', 'code' => 'MTRSOP'],
                    ['name' => 'Standard Preparation SOP', 'code' => 'STPSOP'],
                    ['name' => 'Instrument Qualification & Calibration SOP', 'code' => 'IQCSOP'],
                    ['name' => 'Chromatography System SOP', 'code' => 'CHRSOP'],
                    ['name' => 'Reference Standard Management SOP', 'code' => 'RSMSOP'],
                    ['name' => 'OOS/OOT Investigation SOP', 'code' => 'OOSSOP'],
                    ['name' => 'Lab Deviation Log', 'code' => 'LDLOG'],
                    ['name' => 'Analytical Test Report Template', 'code' => 'ATRT'],
                    ['name' => 'COA (Certificate of Analysis) Template', 'code' => 'COAT'],
                ],
                'regulations' => ['WHO_GMP', 'US_FDA_210_211', 'INDIA_DPCO'],
                'sub_tags' => [
                    ['code' => 'LAB_CTRL', 'name' => 'Laboratory Controls & Testing'],
                    ['code' => 'LAB_STAB', 'name' => 'Stability Testing & Methods'],
                    ['code' => 'LAB_STD', 'name' => 'Reference Standards Management'],
                    ['code' => 'LAB_OOS', 'name' => 'Out of Specification (OOS)'],
                ],
            ],
            [
                'category_name' => 'Validation & Qualification',
                'documents' => [
                    ['name' => 'Validation Master Plan (VMP) Template', 'code' => 'VMPT'],
                    ['name' => 'Process Validation Plan SOP', 'code' => 'PVPSOP'],
                    ['name' => 'Process Validation Protocol Template', 'code' => 'PVPT'],
                    ['name' => 'Process Validation Report Template', 'code' => 'PVRT'],
                    ['name' => 'Cleaning Validation Plan/Protocol/Report Templates', 'code' => 'CVPT'],
                    ['name' => 'Computerized Systems Validation (CSV) SOP', 'code' => 'CSVSOP'],
                    ['name' => 'Data Integrity Policy', 'code' => 'DIPOL'],
                    ['name' => 'Electronic Records & Signatures SOP (align with 21 CFR Part 11)', 'code' => 'ERSSOP'],
                    ['name' => 'Validation Log', 'code' => 'VLOG'],
                ],
                'regulations' => ['WHO_GMP', 'US_FDA_210_211', 'INDIA_DPCO'],
                'sub_tags' => [
                    ['code' => 'VAL_PRC', 'name' => 'Process Validation Expectations'],
                    ['code' => 'VAL_CLN', 'name' => 'Cleaning Validation'],
                    ['code' => 'VAL_CSV', 'name' => 'Computerized Systems Validation'],
                    ['code' => 'VAL_ER', 'name' => 'Electronic Records & Signatures'],
                ],
            ],
            [
                'category_name' => 'Compliance & Risk Management',
                'documents' => [
                    ['name' => 'Risk Assessment Methodology SOP', 'code' => 'RAMSOP'],
                    ['name' => 'Product Quality Risk Assessment Template', 'code' => 'PQRAT'],
                    ['name' => 'Deviation Management SOP (also in QMS Core)', 'code' => 'DMSOP2'],
                    ['name' => 'Investigation SOP (Root Cause Analysis)', 'code' => 'RCASOP'],
                    ['name' => 'CAPA SOP (also in QMS Core)', 'code' => 'CAPASOP2'],
                    ['name' => 'Complaint Handling SOP', 'code' => 'CHSOP'],
                    ['name' => 'Product Recall SOP', 'code' => 'PRCSOP'],
                    ['name' => 'Self-Inspection / Internal Audit SOP', 'code' => 'SIASOP'],
                    ['name' => 'Audit Report Template', 'code' => 'ARTMP'],
                    ['name' => 'Non-Conformance Report Template', 'code' => 'NCRT'],
                ],
                'regulations' => ['WHO_GMP', 'US_FDA_210_211', 'INDIA_DPCO'],
                'sub_tags' => [
                    ['code' => 'CMP_QRM', 'name' => 'Quality Risk Management'],
                    ['code' => 'CMP_INV', 'name' => 'Deviations & Investigations'],
                    ['code' => 'CMP_CAPA', 'name' => 'CAPA Processing'],
                    ['code' => 'CMP_RCL', 'name' => 'Complaints & Recalls'],
                ],
            ],
            [
                'category_name' => 'Training & Personnel',
                'documents' => [
                    ['name' => 'Training Policy', 'code' => 'TPOL'],
                    ['name' => 'Training Needs Assessment SOP', 'code' => 'TNASOP'],
                    ['name' => 'Onboarding Training SOP', 'code' => 'ONBSOP'],
                    ['name' => 'GMP Training SOP', 'code' => 'GMPTSOP'],
                    ['name' => 'Job Description Template', 'code' => 'JDT'],
                    ['name' => 'Training Record Template', 'code' => 'TRT'],
                    ['name' => 'Training Matrix Template', 'code' => 'TMT'],
                    ['name' => 'Competency Assessment Template', 'code' => 'CAT'],
                    ['name' => 'Training Log', 'code' => 'TLOG'],
                ],
                'regulations' => ['WHO_GMP', 'US_FDA_210_211', 'INDIA_DPCO'],
                'sub_tags' => [
                    ['code' => 'TRN_QUAL', 'name' => 'Personnel Qualifications & Training'],
                    ['code' => 'TRN_AWR', 'name' => 'GMP Awareness'],
                    ['code' => 'TRN_HYG', 'name' => 'Personnel Hygiene Requirements'],
                ],
            ],
            [
                'category_name' => 'Distribution & Supply Chain',
                'documents' => [
                    ['name' => 'Distribution SOP', 'code' => 'DSTSOP'],
                    ['name' => 'Cold Chain Management SOP', 'code' => 'CCMSOP'],
                    ['name' => 'Transportation & Storage Conditions SOP', 'code' => 'TSCSOP'],
                    ['name' => 'Supplier/Customer Agreement Template', 'code' => 'SCAT'],
                    ['name' => 'Recall Execution Record', 'code' => 'REREC'],
                    ['name' => 'Distribution Log', 'code' => 'DSTLOG'],
                    ['name' => 'Temperature Monitoring Record (Transport)', 'code' => 'TMRTR'],
                ],
                'regulations' => ['WHO_GMP', 'US_FDA_210_211', 'INDIA_DPCO'],
                'sub_tags' => [
                    ['code' => 'DST_CTRL', 'name' => 'Distribution Controls & Storage'],
                    ['code' => 'DST_CLD', 'name' => 'Cold Chain Management'],
                    ['code' => 'DST_RCL', 'name' => 'Recall Execution Support'],
                ],
            ],
            [
                'category_name' => 'Regulatory & Product Licensing',
                'documents' => [
                    ['name' => 'Product License Application Checklist (India)', 'code' => 'PLAC'],
                    ['name' => 'GMP Certificate Application Checklist', 'code' => 'GMPCAC'],
                    ['name' => 'Regulatory Submission SOP', 'code' => 'RSSOP'],
                    ['name' => 'Label Review SOP', 'code' => 'LRSOP'],
                    ['name' => 'Labeling Compliance Checklist', 'code' => 'LCCL'],
                    ['name' => 'Regulatory Change Control SOP', 'code' => 'RCCSOP'],
                    ['name' => 'dossiers / technical file templates', 'code' => 'DOST'],
                ],
                'regulations' => ['INDIA_DPCO', 'WHO_GMP', 'US_FDA_210_211'],
                'sub_tags' => [
                    ['code' => 'REG_LIC', 'name' => 'Licensing & GMP Certification'],
                    ['code' => 'REG_INS', 'name' => 'Regulatory Inspections'],
                    ['code' => 'REG_LBL', 'name' => 'Labeling Compliance & Submissions'],
                ],
            ],
        ];

        // 3. Process Execution Loop
        foreach ($data as $block) {
            $categoryId = $categories[$block['category_name']] ?? null;
            if (! $categoryId) {
                continue;
            }

            // Map and filter existing base Regulation Tag IDs
            $allTagIds = collect($block['regulations'])
                ->map(fn ($code) => $tags[$code] ?? null)
                ->filter()
                ->toArray();

            // Dynamic Check/Creation of descriptive Sub-Tags
            if (isset($block['sub_tags'])) {
                foreach ($block['sub_tags'] as $subTag) {
                    $createdTag = RegulationTag::query()->firstOrCreate(
                        ['code' => $subTag['code']],
                        ['name' => $subTag['name']]
                    );
                    $allTagIds[] = $createdTag->id;
                }
            }

            $allTagIds = array_unique($allTagIds);

        }
        // ///////////  End Document Types  ////////////////////////////////////////////////////////////////

        $checkerRole = Role::findOrCreate('sop checker', 'web');
        $approverRole = Role::findOrCreate('sop approver', 'web');

        $checker = User::query()->firstOrCreate(
            ['email' => 'Checker@example.com'],
            ['name' => 'SOP Checker', 'password' => 'password', 'department_id' => $qa->id],
        );
        $checker->assignRole($checkerRole);

        $publishedStatusId = TemplateStatus::idFor(TemplateStatus::PUBLISHED);

        $template = DocumentTemplate::query()->firstOrCreate([
            'code' => 'TPL-SOP-GMP',
        ], [
            'name' => 'GMP SOP Template',
            'description' => 'Baseline SOP template for GMP controlled procedures.',
            'department_id' => $qa->id,
            'category_id' => DocumentCategory::query()->firstOrCreate(['code' => 'QMS'], ['name' => 'Quality Management System (QMS) Core'])->id,
            'document_type_id' => DocumentType::query()->where('code', DocumentType::SOP)->valueOrFail('id'),
            'template_status_id' => $publishedStatusId,
            'current_version' => 1,
        ]);

        $version = DocumentTemplateVersion::query()->firstOrCreate([
            'document_template_id' => $template->id,
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
                'content' => "<p>{$title} for {{department}} document {{document_number}} using {{equipment_category}} equipment.</p>",
                'is_required' => true,
            ]);
        }

        foreach ([
            'department' => [VariableDataType::DEPARTMENT, null, true],
            'prepared_by' => [VariableDataType::EMPLOYEE, null, false],
            'approved_by' => [VariableDataType::EMPLOYEE, null, false],
            'equipment' => [VariableDataType::TEXT, null, false],
            'equipment_category' => [VariableDataType::SELECT, [
                'production' => 'Production Equipment',
                'packaging' => 'Packaging Equipment',
                'lab' => 'Laboratory Equipment',
            ], false],
            'effective_date' => [VariableDataType::DATE, null, false],
            'review_date' => [VariableDataType::DATE, null, false],
            'document_number' => [VariableDataType::DOCUMENT_NUMBER, null, true],
        ] as $name => [$typeCode, $options, $required]) {
            $version->variables()->firstOrCreate([
                'name' => $name,
            ], [
                'label' => str($name)->replace('_', ' ')->title()->toString(),
                'variable_data_type_id' => VariableDataType::idFor($typeCode),
                'options' => $options,
                'required' => $required,
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

        $logTemplate = DocumentTemplate::query()->firstOrCreate([
            'code' => 'TPL-LOG-GMP',
        ], [
            'name' => 'GMP Log Document Template',
            'description' => 'Controlled log document template requiring an effective SOP reference.',
            'department_id' => $qa->id,
            'category_id' => DocumentCategory::query()->firstOrCreate(['code' => 'FAC'], ['name' => 'Facility & Equipment'])->id,
            'document_type_id' => DocumentType::query()->where('code', DocumentType::LOG)->valueOrFail('id'),
            'template_status_id' => $publishedStatusId,
            'current_version' => 1,
        ]);

        $logVersion = DocumentTemplateVersion::query()->firstOrCreate([
            'document_template_id' => $logTemplate->id,
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

        foreach ([
            'department' => [VariableDataType::DEPARTMENT, null, true],
            'document_number' => [VariableDataType::DOCUMENT_NUMBER, null, true],
            'referenced_sop' => [VariableDataType::SOP_REFERENCE, null, false],
            'batch_number' => [VariableDataType::TEXT, null, false],
            'product_name' => [VariableDataType::TEXT, null, false],
            'log_type' => [VariableDataType::RADIO, [
                'execution' => 'Execution Log',
                'cleaning' => 'Cleaning Log',
                'maintenance' => 'Maintenance Log',
            ], false],
        ] as $name => [$typeCode, $options, $required]) {
            $logVersion->variables()->firstOrCreate([
                'name' => $name,
            ], [
                'label' => str($name)->replace('_', ' ')->title()->toString(),
                'variable_data_type_id' => VariableDataType::idFor($typeCode),
                'options' => $options,
                'required' => $required,
            ]);
        }

        $standardTemplates = [
            ['code' => 'TPL-STRUCTURED-GMP', 'name' => 'GMP Structured Table Template', 'description' => 'Controlled specification, protocol, report, and validation template with acceptance criteria and traceable results.', 'type' => 'SPEC', 'category' => 'QMS', 'sections' => [
                ['title' => 'Objective and Scope', 'type' => DocumentTemplateSection::TYPE_TEXT, 'content' => '<p>Define the objective, scope, applicable product or process, and governing references.</p>'],
                ['title' => 'Requirements and Acceptance Criteria', 'type' => DocumentTemplateSection::TYPE_TABLE, 'content' => '<p>Record each requirement, method, result, unit, and acceptance decision.</p>', 'tables' => [['title' => 'Requirements and results', 'row_count' => 10, 'fields' => ['Requirement / parameter', 'Method / reference', 'Acceptance criteria', 'Result', 'Pass / Fail']]]],
                ['title' => 'Results and Evaluation', 'type' => DocumentTemplateSection::TYPE_TEXT, 'content' => '<p>Summarize results, exceptions, deviations, and scientific or quality conclusions.</p>'],
                ['title' => 'Approval and References', 'type' => DocumentTemplateSection::TYPE_SIGNATURES, 'content' => '<p>Prepared, reviewed, and approved in accordance with the applicable workflow.</p>'],
            ]],
            ['code' => 'TPL-CONTROLLED-FORM-GMP', 'name' => 'GMP Controlled Form Template', 'description' => 'Blank controlled form for contemporaneous GMP data capture, review, and record retention.', 'type' => DocumentType::FORM, 'category' => 'QMS', 'sections' => [
                ['title' => 'Record Identification', 'type' => DocumentTemplateSection::TYPE_TABLE, 'content' => '<p>Complete before execution.</p>', 'tables' => [['title' => 'Controlled-form identification', 'execution_layout' => 'field_value', 'fields' => ['Form number', 'Department / area', 'Product / process', 'Batch / lot number', 'Date of execution']]]],
                ['title' => 'Data Entry', 'type' => DocumentTemplateSection::TYPE_TABLE, 'content' => '<p>Enter data at the time of performance. Do not leave required fields blank.</p>', 'tables' => [['title' => 'Contemporaneous data entries', 'row_count' => 10, 'fields' => ['Date / time', 'Activity / parameter', 'Observation / result', 'Unit', 'Performed by', 'Verified by']]]],
                ['title' => 'Review and Disposition', 'type' => DocumentTemplateSection::TYPE_TEXT, 'content' => '<p>Record review outcome, discrepancies, corrections, and disposition.</p>'],
                ['title' => 'Signatures', 'type' => DocumentTemplateSection::TYPE_SIGNATURES, 'content' => '<p>Completed by, independently verified by, and approved by.</p>'],
            ]],
            ['code' => 'TPL-BMR-BPR-GMP', 'name' => 'GMP Batch Manufacturing / Packaging Record Template', 'description' => 'Controlled batch record template for manufacturing or packaging execution with reconciliation and independent verification.', 'type' => 'BMR', 'category' => 'PRD', 'sections' => [
                ['title' => 'Batch and Product Details', 'type' => DocumentTemplateSection::TYPE_TABLE, 'content' => '<p>Complete batch number, product, material, lot, planned quantity, and unit details before execution.</p>', 'tables' => [['title' => 'Batch identification', 'execution_layout' => 'field_value', 'fields' => ['Product name', 'Product code', 'Batch number', 'Batch size', 'Manufacturing / packaging date', 'Expiry date']]]],
                ['title' => 'Materials and Reconciliation', 'type' => DocumentTemplateSection::TYPE_TABLE, 'content' => '<p>Record issued, used, returned, rejected, and reconciled quantities.</p>', 'tables' => [
                    ['title' => 'Raw and packaging materials', 'row_count' => 12, 'fields' => ['Material name / code', 'Supplier batch / lot', 'Internal control number', 'Required quantity', 'Issued quantity', 'Unit', 'Dispensed / issued by', 'Verified by']],
                    ['title' => 'Material reconciliation', 'row_count' => 8, 'fields' => ['Material / component', 'Issued quantity', 'Used quantity', 'Returned quantity', 'Rejected / destroyed quantity', 'Variance', 'Reconciliation status']],
                ]],
                ['title' => 'Process / Packaging Steps', 'type' => DocumentTemplateSection::TYPE_CHECKLIST, 'content' => '<p>Complete every step in sequence. Each critical entry requires independent verification.</p>', 'tables' => [['title' => 'Manufacturing / packaging instructions', 'row_count' => 15, 'fields' => ['Step / instruction', 'Acceptance requirement', 'Observed result', 'Completed', 'Performed by / date', 'Verified by / date']]]],
                ['title' => 'Yield, Deviations, and Batch Review', 'type' => DocumentTemplateSection::TYPE_TEXT, 'content' => '<p>Record actual yield, reconciliation status, deviation references, and final QA disposition.</p>'],
                ['title' => 'Approval Signatures', 'type' => DocumentTemplateSection::TYPE_SIGNATURES, 'content' => '<p>Production completion, independent verification, and final batch review.</p>'],
            ]],
            ['code' => 'TPL-CHECKLIST-GMP', 'name' => 'GMP Execution Checklist Template', 'description' => 'Controlled checklist for line clearance, cleaning, inspection, and other GMP verification activities.', 'type' => 'CHECKLIST', 'category' => 'FAC', 'sections' => [
                ['title' => 'Checklist Identification', 'type' => DocumentTemplateSection::TYPE_TABLE, 'content' => '<p>Identify the area, equipment, batch or activity, and applicable SOP.</p>', 'tables' => [['title' => 'Checklist identification', 'execution_layout' => 'field_value', 'fields' => ['Area / room', 'Equipment / line', 'Batch / activity', 'Applicable SOP', 'Date / shift']]]],
                ['title' => 'Checks', 'type' => DocumentTemplateSection::TYPE_CHECKLIST, 'content' => '<p>Mark Pass, Fail, or N/A for every item. Explain all N/A responses and record deviations for failures.</p>', 'tables' => [['title' => 'GMP checks', 'row_count' => 12, 'fields' => ['Check item', 'Acceptance criteria', 'Pass / Fail / N/A', 'Observation / comments', 'Checked by / date', 'Verified by / date', 'Deviation reference']]]],
                ['title' => 'Disposition and Signatures', 'type' => DocumentTemplateSection::TYPE_SIGNATURES, 'content' => '<p>Independent verification and QA disposition are required before submission.</p>'],
            ]],
            ['code' => 'TPL-ANNEXURE-GMP', 'name' => 'GMP Annexure and Evidence Package Template', 'description' => 'Controlled attachment package for certificates, drawings, photographs, raw data, and supporting evidence.', 'type' => 'ANNEXURE', 'category' => 'QMS', 'sections' => [
                ['title' => 'Evidence Package Index', 'type' => DocumentTemplateSection::TYPE_TABLE, 'content' => '<p>List every attachment and its relationship to the parent controlled document.</p>', 'tables' => [['title' => 'Annexure index', 'row_count' => 10, 'fields' => ['Annexure number', 'Document / evidence title', 'Document reference / version', 'Source / owner', 'Date generated', 'Page count', 'Integrity / review status', 'Remarks']]]],
                ['title' => 'Evidence Description', 'type' => DocumentTemplateSection::TYPE_TEXT, 'content' => '<p>Describe the evidence, source, date generated, and any limitations or superseded material.</p>'],
                ['title' => 'Attachment Review and Approval', 'type' => DocumentTemplateSection::TYPE_SIGNATURES, 'content' => '<p>Confirm that required attachments are present, readable, immutable, and integrity-checked.</p>'],
            ]],
        ];

        foreach ($standardTemplates as $definition) {
            $this->seedStandardTemplate($definition, $qa, $publishedStatusId);
        }
    }

    /** @param array<string, mixed> $definition */
    private function seedStandardTemplate(array $definition, Department $qa, int $publishedStatusId): void
    {
        $template = DocumentTemplate::query()->updateOrCreate(
            ['code' => $definition['code']],
            [
                'name' => $definition['name'],
                'description' => $definition['description'],
                'department_id' => $qa->id,
                'category_id' => DocumentCategory::query()->where('code', $definition['category'])->valueOrFail('id'),
                'document_type_id' => DocumentType::query()->where('code', $definition['type'])->valueOrFail('id'),
                'template_status_id' => $publishedStatusId,
                'current_version' => 1,
            ],
        );

        $version = DocumentTemplateVersion::query()->updateOrCreate(
            ['document_template_id' => $template->id, 'version' => 1],
            ['content_json' => [], 'effective_date' => now()->toDateString(), 'change_reason' => 'Initial standard GMP template', 'template_status_id' => $publishedStatusId],
        );

        foreach ($definition['sections'] as $order => $section) {
            $sectionValues = [
                'title' => $section['title'],
                'section_type' => $section['type'],
                'content' => $section['content'],
                'is_required' => true,
                'include_in_toc' => true,
            ];

            if (array_key_exists('configuration', $section)) {
                $sectionValues['configuration'] = $section['configuration'];
            }

            if (array_key_exists('tables', $section)) {
                $sectionValues['configuration'] = [
                    'execution_tables' => $this->executionTableDefinitions($section['tables']),
                ];
            }

            $version->sections()->updateOrCreate(
                ['section_order' => $order + 1],
                $sectionValues,
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $tables
     * @return array<int, array<string, mixed>>
     */
    private function executionTableDefinitions(array $tables): array
    {
        $numericFields = [
            'Required quantity', 'Issued quantity', 'Used quantity', 'Returned quantity',
            'Rejected / destroyed quantity', 'Variance', 'Page count',
        ];
        $booleanFields = ['Pass / Fail', 'Reconciliation status', 'Completed', 'Pass / Fail / N/A'];

        return collect($tables)->map(function (array $table, int $tableOrder) use ($numericFields, $booleanFields): array {
            return [
                'title' => $table['title'],
                'table_order' => $tableOrder + 1,
                'execution_layout' => $table['execution_layout'] ?? 'table',
                'row_count' => $table['row_count'] ?? 1,
                'fields' => collect($table['fields'])->map(function (string $label, int $itemOrder) use ($numericFields, $booleanFields): array {
                    return [
                        'label' => $label,
                        'item_order' => $itemOrder + 1,
                        'value_type' => match (true) {
                            in_array($label, $numericFields, true) => ControlledDocumentSectionItem::VALUE_NUMERIC,
                            in_array($label, $booleanFields, true) => ControlledDocumentSectionItem::VALUE_BOOLEAN,
                            default => ControlledDocumentSectionItem::VALUE_TEXT,
                        },
                        'is_required' => true,
                    ];
                })->all(),
            ];
        })->all();
    }
}
