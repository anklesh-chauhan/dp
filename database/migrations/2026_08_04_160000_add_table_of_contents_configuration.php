<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_templates', function (Blueprint $table): void {
            $table->json('toc_configuration')->nullable()->after('footer_zones');
        });

        Schema::table('document_template_sections', function (Blueprint $table): void {
            $table->unsignedTinyInteger('heading_level')->default(1)->after('section_order');
            $table->boolean('include_in_toc')->default(true)->after('is_required');
            $table->string('toc_title')->nullable()->after('include_in_toc');
        });

        Schema::table('controlled_document_sections', function (Blueprint $table): void {
            $table->unsignedTinyInteger('heading_level')->default(1)->after('section_order');
            $table->boolean('include_in_toc')->default(true)->after('content');
            $table->string('toc_title')->nullable()->after('include_in_toc');
        });
    }

    public function down(): void
    {
        Schema::table('controlled_document_sections', fn (Blueprint $table): mixed => $table->dropColumn(['heading_level', 'include_in_toc', 'toc_title']));
        Schema::table('document_template_sections', fn (Blueprint $table): mixed => $table->dropColumn(['heading_level', 'include_in_toc', 'toc_title']));
        Schema::table('report_templates', fn (Blueprint $table): mixed => $table->dropColumn('toc_configuration'));
    }
};
