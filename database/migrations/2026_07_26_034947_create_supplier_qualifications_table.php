<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\SupplierCategory;
use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Enums\SupplierRiskLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_qualifications', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_number')->unique();
            $table->string('legal_name')->index();
            $table->string('site_name')->nullable();
            $table->string('category')->default(SupplierCategory::Other->value)->index();
            $table->string('status')->default(SupplierQualificationStatus::Draft->value)->index();
            $table->string('risk_level')->default(SupplierRiskLevel::Medium->value)->index();
            $table->text('material_service_scope');
            $table->string('country_code', 2)->nullable()->index();
            $table->text('site_address')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('qualification_rationale')->nullable();
            $table->timestamp('qualification_started_at')->nullable();
            $table->date('audit_due_at')->nullable();
            $table->timestamp('audit_completed_at')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->date('qualification_expires_at')->nullable()->index();
            $table->date('next_review_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('disqualified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_qualifications');
    }
};
