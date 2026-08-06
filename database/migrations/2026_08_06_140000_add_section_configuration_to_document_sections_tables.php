<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_template_sections', function (Blueprint $table): void {
            $table->json('configuration')->nullable()->after('content');
        });

        Schema::table('controlled_document_sections', function (Blueprint $table): void {
            $table->json('configuration')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('document_template_sections', function (Blueprint $table): void {
            $table->dropColumn('configuration');
        });

        Schema::table('controlled_document_sections', function (Blueprint $table): void {
            $table->dropColumn('configuration');
        });
    }
};
