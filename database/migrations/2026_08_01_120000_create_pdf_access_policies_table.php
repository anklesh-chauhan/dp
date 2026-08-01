<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_access_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('effect', 10)->default('allow');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('can_view')->default(true);
            $table->boolean('can_print')->default(false);
            $table->boolean('can_download')->default(false);
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_status_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'effect', 'priority'], 'pdf_policy_evaluation_index');
        });

        Schema::create('pdf_access_policy_role', function (Blueprint $table): void {
            $table->foreignId('pdf_access_policy_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['pdf_access_policy_id', 'role_id'], 'pdf_policy_role_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdf_access_policy_role');
        Schema::dropIfExists('pdf_access_policies');
    }
};
