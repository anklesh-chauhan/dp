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
        Schema::table('knowledge_guides', function (Blueprint $table) {
            $table->string('product_module')
                ->default('dms')
                ->after('slug')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_guides', function (Blueprint $table) {
            $table->dropIndex(['product_module']);
            $table->dropColumn('product_module');
        });
    }
};
