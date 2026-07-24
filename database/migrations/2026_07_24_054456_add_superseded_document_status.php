<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('document_statuses')->updateOrInsert(
            ['code' => 'superseded'],
            [
                'name' => 'Superseded',
                'sort_order' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('document_statuses')
            ->whereIn('code', ['obsolete', 'archived', 'retention_completed', 'destroyed', 'rejected'])
            ->increment('sort_order');
    }

    public function down(): void
    {
        $supersededStatusId = DB::table('document_statuses')->where('code', 'superseded')->value('id');
        $obsoleteStatusId = DB::table('document_statuses')->where('code', 'obsolete')->value('id');

        if ($supersededStatusId !== null && $obsoleteStatusId !== null) {
            DB::table('sop_documents')
                ->where('document_status_id', $supersededStatusId)
                ->update(['document_status_id' => $obsoleteStatusId]);
        }

        DB::table('document_statuses')->where('code', 'superseded')->delete();

        DB::table('document_statuses')
            ->whereIn('code', ['obsolete', 'archived', 'retention_completed', 'destroyed', 'rejected'])
            ->decrement('sort_order');
    }
};
