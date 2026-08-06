<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controlled_document_section_items', function (Blueprint $table): void {
            $table->dateTime('scheduled_at')->nullable()->after('item_order');
            $table->string('review_status', 20)->default('pending')->after('verified_at');
            $table->foreignId('reviewed_by')->nullable()->after('review_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('controlled_document_section_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['scheduled_at', 'review_status', 'reviewed_at']);
        });
    }
};
