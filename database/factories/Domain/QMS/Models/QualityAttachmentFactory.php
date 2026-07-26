<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<QualityAttachment> */
final class QualityAttachmentFactory extends Factory
{
    protected $model = QualityAttachment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $attachmentUuid = (string) Str::uuid();

        return [
            'attachment_uuid' => $attachmentUuid,
            'attachable_type' => Deviation::class,
            'attachable_id' => Deviation::factory(),
            'disk' => 'local',
            'path' => "qms/attachments/{$attachmentUuid}.pdf",
            'original_name' => 'quality-evidence.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1_024,
            'content_hash' => hash('sha256', 'factory-quality-evidence'),
            'description' => fake()->sentence(),
            'uploaded_by' => User::factory(),
            'uploaded_at' => now(),
        ];
    }
}
