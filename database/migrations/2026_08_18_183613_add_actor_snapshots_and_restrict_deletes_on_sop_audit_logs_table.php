<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sop_audit_logs', function (Blueprint $table): void {
            $table->string('actor_name')->nullable()->after('user_id');
            $table->string('actor_email')->nullable()->after('actor_name');
        });

        DB::statement('
            UPDATE sop_audit_logs AS logs
            SET actor_name = users.name, actor_email = users.email
            FROM users
            WHERE logs.user_id = users.id
        ');

        Schema::table('sop_audit_logs', function (Blueprint $table): void {
            $table->dropForeign(['document_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['document_template_id']);
        });

        Schema::table('sop_audit_logs', function (Blueprint $table): void {
            $table->foreign('document_id')
                ->references('id')
                ->on('controlled_documents')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('document_template_id')
                ->references('id')
                ->on('document_templates')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sop_audit_logs', function (Blueprint $table): void {
            $table->dropForeign(['document_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['document_template_id']);
        });

        Schema::table('sop_audit_logs', function (Blueprint $table): void {
            $table->foreign('document_id')
                ->references('id')
                ->on('controlled_documents')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('document_template_id')
                ->references('id')
                ->on('document_templates')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->dropColumn(['actor_name', 'actor_email']);
        });
    }
};
