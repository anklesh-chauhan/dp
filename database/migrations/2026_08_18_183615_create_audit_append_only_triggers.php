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
        'sop_audit_logs',
        'security_audit_events',
    ];

    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION prevent_gxp_audit_mutation()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'GxP audit records are append-only and cannot be updated or deleted.';
END;
$$;
SQL);

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

        DB::unprepared('DROP FUNCTION IF EXISTS prevent_gxp_audit_mutation();');
    }
};
