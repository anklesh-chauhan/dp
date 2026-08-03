<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_original_artifacts', function (Blueprint $table): void {
            $table->string('preview_path')->nullable()->after('sha256');
            $table->string('preview_mime_type')->nullable()->after('preview_path');
            $table->string('preview_sha256', 64)->nullable()->after('preview_mime_type');
            $table->timestamp('preview_generated_at')->nullable()->after('preview_sha256');
            $table->text('preview_error')->nullable()->after('preview_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_original_artifacts', function (Blueprint $table): void {
            $table->dropColumn(['preview_path', 'preview_mime_type', 'preview_sha256', 'preview_generated_at', 'preview_error']);
        });
    }
};
