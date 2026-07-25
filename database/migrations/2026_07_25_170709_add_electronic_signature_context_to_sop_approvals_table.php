<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sop_approvals', function (Blueprint $table): void {
            $table->ipAddress('signature_ip_address')
                ->nullable()
                ->after('signature_hash');
            $table->text('signature_user_agent')
                ->nullable()
                ->after('signature_ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sop_approvals', function (Blueprint $table): void {
            $table->dropColumn([
                'signature_ip_address',
                'signature_user_agent',
            ]);
        });
    }
};
