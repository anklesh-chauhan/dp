<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->after('template_version_id')
                ->constrained()
                ->nullOnDelete();
            $table->json('organization_snapshot')->nullable()->after('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn(['organization_id', 'organization_snapshot']);
        });
    }
};
