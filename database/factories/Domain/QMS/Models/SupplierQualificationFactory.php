<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\SupplierCategory;
use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Enums\SupplierRiskLevel;
use App\Domain\QMS\Models\SupplierQualification;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupplierQualification> */
final class SupplierQualificationFactory extends Factory
{
    protected $model = SupplierQualification::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'legal_name' => fake()->company(),
            'site_name' => fake()->city().' Manufacturing Site',
            'category' => SupplierCategory::RawMaterial,
            'status' => SupplierQualificationStatus::Draft,
            'risk_level' => SupplierRiskLevel::Medium,
            'material_service_scope' => fake()->sentence(),
            'country_code' => fake()->countryCode(),
            'site_address' => fake()->address(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'department_id' => Department::factory(),
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
            'approved_by' => null,
            'qualification_started_at' => null,
            'audit_due_at' => today()->addDays(45),
            'audit_completed_at' => null,
            'qualified_at' => null,
            'qualification_expires_at' => null,
            'next_review_at' => today()->addYear(),
            'suspended_at' => null,
            'disqualified_at' => null,
        ];
    }
}
