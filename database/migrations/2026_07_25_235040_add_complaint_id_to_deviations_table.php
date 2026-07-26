<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            $table->foreignId('complaint_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('complaint_id');
        });
    }
};
