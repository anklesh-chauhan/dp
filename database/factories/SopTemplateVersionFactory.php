<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SopTemplateVersion>
 */
class SopTemplateVersionFactory extends Factory
{
    protected $model = SopTemplateVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sop_template_id' => SopTemplate::factory(),
            'version' => 1,
            'content_json' => [],
            'effective_date' => now()->toDateString(),
            'change_reason' => fake()->optional()->sentence(),
            'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
            'approval_status' => TemplateApprovalStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'template_status_id' => TemplateStatus::idFor(TemplateStatus::PUBLISHED),
            'approval_status' => TemplateApprovalStatus::Approved,
        ]);
    }
}
