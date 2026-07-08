<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApprovalStepType;
use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentType;
use App\Models\RegulationTag;
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

        'ViewAny:NumberSeries',
        'View:NumberSeries',
        'Update:NumberSeries',

        'View:ManageNumberSeriesSettings',
    ];

    public function run(): void
    {
        $this->call(LookupTableSeeder::class);
        $this->call(NumberSeriesSeeder::class);

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

        ////////////////////////////////////////////////////////////////
        ////////////////  Document Types  //////////////////////////////
        ////////////////////////////////////////////////////////////////
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
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
                ]
            ],
        ];

        // 3. Process Execution Loop
        foreach ($data as $block) {
            $categoryId = $categories[$block['category_name']] ?? null;
            if (!$categoryId) {
                continue;
            }

            // Map and filter existing base Regulation Tag IDs
            $allTagIds = collect($block['regulations'])
                ->map(fn($code) => $tags[$code] ?? null)
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

            // 4. Document Types creation and relationship Syncing
            foreach ($block['documents'] as $doc) {
                /** @var DocumentType $docType */
                $docType = DocumentType::query()->firstOrCreate(
                    ['code' => $doc['code']],
                    [
                        'name' => $doc['name'],
                        'category_id' => $categoryId,
                        'requires_sop_reference' => false,
                        'is_issuable' => false,
                    ]
                );

                // Sync pivot connections efficiently
                $docType->regulationTags()->sync($allTagIds);
            }
        }
        /////////////  End Document Types  ////////////////////////////////////////////////////////////////

        // Permissions
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
            'MarkObsolete:SopDocument',
            'Archive:SopDocument',
            'CompleteRetention:SopDocument',
            'Destroy:SopDocument',
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
            'category_id' => DocumentCategory::query()->firstOrCreate(['code' => 'QMS'], ['name' => 'Quality Management System (QMS) Core'])->id,
            'document_type_id' => DocumentType::query()->firstOrCreate(['code' => 'QSO'], ['name' => 'QMS Scope & Objectives'])->id,
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

        $logTemplate = SopTemplate::query()->firstOrCreate([
            'code' => 'TPL-LOG-GMP',
        ], [
            'name' => 'GMP Log Document Template',
            'description' => 'Controlled log document template requiring an effective SOP reference.',
            'department_id' => $qa->id,
            'category_id' => DocumentCategory::query()->firstOrCreate(['code' => 'FAC'], ['name' => 'Facility & Equipment'])->id,
            'document_type_id' => DocumentType::query()->firstOrCreate(['code' => 'PMLOG'], ['name' => 'Preventive Maintenance Log'])->id,
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
    }
}
