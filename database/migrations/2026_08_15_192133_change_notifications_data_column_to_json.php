<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filament unread counts use where('data->format', 'filament'), which PostgreSQL
     * compiles to data->>'format'. That operator requires json/jsonb, not text.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE jsonb USING data::jsonb');

            return;
        }

        Schema::table('notifications', function (Blueprint $table): void {
            $table->json('data')->change();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');

            return;
        }

        Schema::table('notifications', function (Blueprint $table): void {
            $table->text('data')->change();
        });
    }
};
