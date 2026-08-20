<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'change_control_audit_events',
        'deviation_audit_events',
        'investigation_audit_events',
        'capa_audit_events',
        'complaint_audit_events',
        'internal_audit_events',
        'audit_finding_events',
        'risk_assessment_events',
        'supplier_qualification_events',
        'management_review_events',
        'product_quality_review_events',
        'product_recall_events',
        'product_return_events',
        'laboratory_oos_event_events',
        'validation_master_plan_events',
        'equipment_asset_events',
        'equipment_qualification_events',
        'equipment_calibration_events',
        'equipment_maintenance_events',
        'schedule_m_gap_assessment_events',
        'site_master_file_events',
        'csv_validation_project_events',
        'csv_signed_decisions',
        'batch_release_events',
        'computerized_system_incident_events',
        'product_license_audit_events',
        'document_template_approval_events',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            DB::unprepared("
                DROP TRIGGER IF EXISTS prevent_gxp_audit_mutation_trigger ON {$table};
                CREATE TRIGGER prevent_gxp_audit_mutation_trigger
                BEFORE UPDATE OR DELETE ON {$table}
                FOR EACH ROW
                EXECUTE FUNCTION prevent_gxp_audit_mutation();
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::unprepared("DROP TRIGGER IF EXISTS prevent_gxp_audit_mutation_trigger ON {$table};");
        }
    }
};
