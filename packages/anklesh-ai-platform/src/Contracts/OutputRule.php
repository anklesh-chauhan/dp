<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Contracts;

use Anklesh\AiPlatform\Data\ValidationIssue;

interface OutputRule
{
    /**
     * @param  array<string, mixed>|string  $output
     * @return iterable<ValidationIssue>
     */
    public function validate(array|string $output): iterable;
}
