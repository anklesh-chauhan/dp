<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competency_curricula', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('role_name')->nullable()->index();
            $table->foreignId('sop_role_id')->nullable()->constrained('sop_roles')->nullOnDelete();
            $table->text('description')->nullable();
            $table->unsignedInteger('requalification_months')->nullable();
            $table->string('gate_key')->nullable()->index();
            $table->boolean('is_active')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competency_curricula');
    }
};
