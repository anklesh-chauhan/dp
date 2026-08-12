<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('date_display_format')->default('d/m/Y')->after('timezone');
            $table->string('datetime_display_format')->default('d/m/Y H:i')->after('date_display_format');
            $table->string('time_display_format')->default('H:i')->after('datetime_display_format');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn([
                'date_display_format',
                'datetime_display_format',
                'time_display_format',
            ]);
        });
    }
};
