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
            $table->timestamp('released_for_effectiveness_at')
                ->nullable()
                ->after('review_date')
                ->index();
            $table->foreignId('released_for_effectiveness_by')
                ->nullable()
                ->after('released_for_effectiveness_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('controlled_documents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('released_for_effectiveness_by');
            $table->dropColumn('released_for_effectiveness_at');
        });
    }
};
