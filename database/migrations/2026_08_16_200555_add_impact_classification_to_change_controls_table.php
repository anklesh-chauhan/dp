<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_controls', function (Blueprint $table): void {
            $table->string('impact_classification')
                ->default('minor')
                ->after('rationale');
        });
    }

    public function down(): void
    {
        Schema::table('change_controls', function (Blueprint $table): void {
            $table->dropColumn('impact_classification');
        });
    }
};
