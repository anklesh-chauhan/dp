<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteMasterFile> */
final class SiteMasterFileFactory extends Factory
{
    protected $model = SiteMasterFile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'status' => SiteMasterFileStatus::Draft,
            'version' => 1,
            'sections' => SiteMasterFile::defaultSections(),
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
            'published_by' => null,
            'published_at' => null,
        ];
    }
}
