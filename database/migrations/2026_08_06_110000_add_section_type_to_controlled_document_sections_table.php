<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controlled_document_sections', function (Blueprint $table): void {
            $table->string('section_type', 50)->default('rich_text')->after('section_order');
        });
    }

    public function down(): void
    {
        Schema::table('controlled_document_sections', function (Blueprint $table): void {
            $table->dropColumn('section_type');
        });
    }
};
