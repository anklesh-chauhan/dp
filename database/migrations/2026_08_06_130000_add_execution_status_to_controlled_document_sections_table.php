<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controlled_document_sections', function (Blueprint $table): void {
            $table->string('execution_status', 30)->default('pending')->after('section_type');
            $table->foreignId('completed_by')->nullable()->after('execution_status')->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->after('completed_by');
        });
    }

    public function down(): void
    {
        Schema::table('controlled_document_sections', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('completed_by');
            $table->dropColumn(['execution_status', 'completed_at']);
        });
    }
};
