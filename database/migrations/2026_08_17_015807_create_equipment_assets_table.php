<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\EquipmentAssetCategory;
use App\Domain\QMS\Enums\EquipmentAssetCriticality;
use App\Domain\QMS\Enums\EquipmentAssetStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_number')->unique();
            $table->string('name');
            $table->string('asset_tag')->nullable()->index();
            $table->string('location')->nullable();
            $table->string('category')->default(EquipmentAssetCategory::Other->value)->index();
            $table->string('criticality')->default(EquipmentAssetCriticality::Medium->value)->index();
            $table->boolean('gxp_impact')->default(false)->index();
            $table->string('status')->default(EquipmentAssetStatus::Active->value)->index();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validation_master_plan_id')->nullable()->constrained('validation_master_plans')->nullOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->index();
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('commissioned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_assets');
    }
};
