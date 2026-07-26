<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_attachments', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->after('size_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('quality_attachments', function (Blueprint $table) {
            $table->dropColumn('content_hash');
        });
    }
};
