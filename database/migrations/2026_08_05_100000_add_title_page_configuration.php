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
            $table->json('title_page_configuration')->nullable()->after('toc_configuration');
        });
    }

    public function down(): void
    {
        Schema::table('report_templates', function (Blueprint $table): void {
            $table->dropColumn('title_page_configuration');
        });
    }
};
