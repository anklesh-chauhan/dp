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
        Schema::table('sop_templates', function (Blueprint $table) {
            $table->string('generation_status')->default('pending'); // pending, processing, rendering_sections, completed, failed
            $table->integer('generation_progress')->default(0); // 0 to 100 %
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sop_templates', function (Blueprint $table) {
            $table->dropColumn('generation_status');
            $table->dropColumn('generation_progress');
        });
    }
};
