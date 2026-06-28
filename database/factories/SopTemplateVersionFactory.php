<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TemplateStatus;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\User;
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
            'effective_date' => now()->addDays(7)->toDateString(),
            'change_reason' => 'Initial version',
            'status' => TemplateStatus::Draft,
            'created_by' => User::factory(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => TemplateStatus::Published]);
    }
}
