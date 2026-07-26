<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\AttachmentIntegrityStatus;
use App\Domain\QMS\Models\QualityAttachment;
use App\Domain\Shared\Contracts\ContentIntegrityHasher;

final class QualityAttachmentIntegrityService
{
    public function __construct(
        private readonly ContentIntegrityHasher $contentIntegrityHasher,
    ) {}

    public function status(QualityAttachment $attachment): AttachmentIntegrityStatus
    {
        if (blank($attachment->content_hash)) {
            return AttachmentIntegrityStatus::Unverified;
        }

        $actualHash = $this->contentIntegrityHasher->hash(
            (string) $attachment->disk,
            (string) $attachment->path,
        );

        if ($actualHash === null) {
            return AttachmentIntegrityStatus::Missing;
        }

        return hash_equals((string) $attachment->content_hash, $actualHash)
            ? AttachmentIntegrityStatus::Verified
            : AttachmentIntegrityStatus::Tampered;
    }
}
