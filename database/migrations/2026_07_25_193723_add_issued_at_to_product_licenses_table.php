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
        Schema::table('product_licenses', function (Blueprint $table) {
            $table->timestamp('issued_at')
                ->nullable()
                ->after('activated_at')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_licenses', function (Blueprint $table) {
            $table->dropColumn('issued_at');
        });
    }
};
