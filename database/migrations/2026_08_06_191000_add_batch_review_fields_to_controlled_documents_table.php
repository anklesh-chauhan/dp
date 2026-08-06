<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->decimal('planned_yield', 20, 8)->nullable()->after('product_name');
            $table->decimal('actual_yield', 20, 8)->nullable()->after('planned_yield');
            $table->string('yield_unit', 30)->nullable()->after('actual_yield');
            $table->string('reconciliation_status', 20)->default('pending')->after('yield_unit');
            $table->string('final_review_status', 20)->default('pending')->after('reconciliation_status');
            $table->foreignId('final_reviewed_by')->nullable()->after('final_review_status')->constrained('users')->nullOnDelete();
            $table->timestamp('final_reviewed_at')->nullable()->after('final_reviewed_by');
            $table->text('final_review_notes')->nullable()->after('final_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('final_reviewed_by');
            $table->dropColumn(['planned_yield', 'actual_yield', 'yield_unit', 'reconciliation_status', 'final_review_status', 'final_reviewed_at', 'final_review_notes']);
        });
    }
};
