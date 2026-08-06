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
            $table->string('log_frequency', 20)->nullable()->after('purpose');
            $table->date('log_period_start')->nullable()->after('log_frequency');
            $table->date('log_period_end')->nullable()->after('log_period_start');
            $table->foreignId('supervisor_id')->nullable()->after('log_period_end')->constrained('users')->nullOnDelete();
            $table->string('log_review_status', 20)->default('pending')->after('supervisor_id');
        });
    }

    public function down(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropColumn(['log_frequency', 'log_period_start', 'log_period_end', 'log_review_status']);
        });
    }
};
