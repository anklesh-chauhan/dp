<?php

declare(strict_types=1);

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
        Schema::table('change_control_audit_events', function (Blueprint $table) {
            $table->uuid('event_uuid')->nullable()->unique()->after('id');
            $table->string('signature_hash', 64)->nullable()->after('context');
            $table->string('signature_ip_address', 45)->nullable()->after('signature_hash');
            $table->text('signature_user_agent')->nullable()->after('signature_ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_control_audit_events', function (Blueprint $table) {
            $table->dropUnique(['event_uuid']);
            $table->dropColumn([
                'event_uuid',
                'signature_hash',
                'signature_ip_address',
                'signature_user_agent',
            ]);
        });
    }
};
