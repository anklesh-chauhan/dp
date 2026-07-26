<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capas', function (Blueprint $table) {
            $table->foreignId('deviation_id')->nullable()->change();
            $table->foreignId('audit_finding_id')
                ->nullable()
                ->unique()
                ->after('deviation_id')
                ->constrained()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('capas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('audit_finding_id');
            $table->foreignId('deviation_id')->nullable(false)->change();
        });
    }
};
