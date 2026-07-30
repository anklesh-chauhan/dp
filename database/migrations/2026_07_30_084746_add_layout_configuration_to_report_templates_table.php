<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_templates', function (Blueprint $table) {
            $table->json('page_settings')->nullable()->after('fields');
            $table->json('header_zones')->nullable()->after('page_settings');
            $table->json('footer_zones')->nullable()->after('header_zones');
        });
    }

    public function down(): void
    {
        Schema::table('report_templates', function (Blueprint $table) {
            $table->dropColumn(['page_settings', 'header_zones', 'footer_zones']);
        });
    }
};
