<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Models\ReportTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportTemplate>
 */
class ReportTemplateFactory extends Factory
{
    public function definition(): array
    {
        $scope = fake()->randomElement(ReportScope::cases());

        return [
            'layout_key' => fake()->unique()->slug(3),
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'scope' => $scope,
            'format' => fake()->randomElement(ReportFormat::cases()),
            'fields' => app(ReportFieldRegistry::class)->defaultFields($scope),
            'page_settings' => app(PrintLayoutRegistry::class)->defaultPageSettings(),
            'header_zones' => app(PrintLayoutRegistry::class)->defaultHeaderZones(),
            'footer_zones' => app(PrintLayoutRegistry::class)->defaultFooterZones(),
            'is_active' => true,
            'is_system' => false,
        ];
    }
}
