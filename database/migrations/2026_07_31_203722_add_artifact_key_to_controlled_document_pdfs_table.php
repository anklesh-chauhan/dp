<?php

declare(strict_types=1);

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
        if (Schema::hasColumn('controlled_document_pdfs', 'artifact_key')) {
            return;
        }

        Schema::table('controlled_document_pdfs', function (Blueprint $table) {
            $table->char('artifact_key', 64)->nullable()->unique()->after('document_issuance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('controlled_document_pdfs', 'artifact_key')) {
            return;
        }

        Schema::table('controlled_document_pdfs', function (Blueprint $table) {
            $table->dropUnique(['artifact_key']);
            $table->dropColumn('artifact_key');
        });
    }
};
