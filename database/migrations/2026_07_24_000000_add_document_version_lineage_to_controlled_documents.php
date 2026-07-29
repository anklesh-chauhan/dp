<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->uuid('document_series_id')->nullable()->after('id');
            $table->foreignId('supersedes_document_id')->nullable()->after('document_series_id')
                ->constrained('controlled_documents')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('revision_reason')->nullable()->after('version');
        });

        DB::table('controlled_documents')->select('id')->orderBy('id')->eachById(function (object $document): void {
            DB::table('controlled_documents')->where('id', $document->id)
                ->update(['document_series_id' => (string) Str::uuid()]);
        });

        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->dropUnique('controlled_documents_document_number_unique');
            $table->unique(['document_number', 'version']);
            $table->unique(['document_series_id', 'version']);
            $table->index(['document_series_id', 'document_status_id']);
        });
    }

    public function down(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->dropUnique(['document_number', 'version']);
            $table->dropUnique(['document_series_id', 'version']);
            $table->dropIndex(['document_series_id', 'document_status_id']);
            $table->unique('document_number');
            $table->dropConstrainedForeignId('supersedes_document_id');
            $table->dropColumn(['document_series_id', 'revision_reason']);
        });
    }
};
