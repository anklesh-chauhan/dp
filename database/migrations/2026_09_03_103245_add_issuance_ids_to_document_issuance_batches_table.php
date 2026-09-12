<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_issuance_batches', function (Blueprint $table): void {
            $table->json('issuance_ids')->nullable()->after('last_issuance_number');
        });
    }

    public function down(): void
    {
        Schema::table('document_issuance_batches', function (Blueprint $table): void {
            $table->dropColumn('issuance_ids');
        });
    }
};
