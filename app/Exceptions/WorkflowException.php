<?php

declare(strict_types=1);

namespace App\Exceptions;

class WorkflowException extends ServiceException
{
    public function title(): string
    {
        return $this->title ?? 'Workflow Error';
    }
}
