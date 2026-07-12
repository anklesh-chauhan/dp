<?php

declare(strict_types=1);

namespace App\Services\AI\Enums;

enum AiTaskStatus: string
{
    case PENDING = 'pending';

    case PROCESSING = 'processing';

    case COMPLETED = 'completed';

    case FAILED = 'failed';

    case CANCELLED = 'cancelled';

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this === self::PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }

    public function isFinished(): bool
    {
        return in_array(
            $this,
            [
                self::COMPLETED,
                self::FAILED,
                self::CANCELLED,
            ],
            true,
        );
    }

    public function isActive(): bool
    {
        return in_array(
            $this,
            [
                self::PENDING,
                self::PROCESSING,
            ],
            true,
        );
    }
}
