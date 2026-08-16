<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->foreignId('submitted_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        $latestSubmissionIds = DB::table('sop_audit_logs')
            ->selectRaw('document_id, max(id) as id')
            ->where('action', 'submitted')
            ->whereNotNull('document_id')
            ->groupBy('document_id');

        $latestSubmitters = DB::table('sop_audit_logs as logs')
            ->joinSub($latestSubmissionIds, 'latest', function (JoinClause $join): void {
                $join->on('logs.id', '=', 'latest.id');
            })
            ->whereNotNull('logs.user_id')
            ->get(['logs.document_id', 'logs.user_id']);

        foreach ($latestSubmitters as $row) {
            DB::table('controlled_documents')
                ->where('id', $row->document_id)
                ->update(['submitted_by' => $row->user_id]);
        }
    }

    public function down(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submitted_by');
        });
    }
};
