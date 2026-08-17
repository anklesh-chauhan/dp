<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Domain\QMS\Models\SiteMasterFileEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SiteMasterFileEvent> */
final class SiteMasterFileEventFactory extends Factory
{
    protected $model = SiteMasterFileEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'site_master_file_id' => SiteMasterFile::factory(),
            'from_status' => SiteMasterFileStatus::Draft,
            'to_status' => SiteMasterFileStatus::InReview,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'signature_hash' => null,
            'signature_ip_address' => null,
            'signature_user_agent' => null,
            'occurred_at' => now(),
        ];
    }
}
