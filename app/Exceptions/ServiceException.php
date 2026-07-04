<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

abstract class ServiceException extends Exception
{
    public function __construct(
        string $message = '',
        protected ?string $title = null,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function title(): string
    {
        return $this->title ?? 'Action Failed';
    }
}
