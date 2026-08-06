<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_attachments', function (Blueprint $table): void {
            $table->string('annexure_number', 50)->nullable()->after('original_name');
            $table->string('attachment_role', 50)->default('supporting_evidence')->after('annexure_number');
            $table->boolean('is_required')->default(false)->after('attachment_role');
            $table->boolean('include_in_print')->default(true)->after('is_required');
        });
    }

    public function down(): void
    {
        Schema::table('quality_attachments', function (Blueprint $table): void {
            $table->dropColumn(['annexure_number', 'attachment_role', 'is_required', 'include_in_print']);
        });
    }
};
