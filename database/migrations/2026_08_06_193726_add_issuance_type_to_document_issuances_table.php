<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_issuances', function (Blueprint $table): void {
            $table->string('issuance_type', 20)->default('reference')->after('issuance_number')->index();
        });
    }

    public function down(): void
    {
        Schema::table('document_issuances', function (Blueprint $table): void {
            $table->dropColumn('issuance_type');
        });
    }
};
