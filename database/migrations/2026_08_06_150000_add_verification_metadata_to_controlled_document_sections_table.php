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
            $table->text('execution_notes')->nullable()->after('completed_at');
            $table->foreignId('verified_by')->nullable()->after('execution_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('controlled_document_sections', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['execution_notes', 'verified_at']);
        });
    }
};
