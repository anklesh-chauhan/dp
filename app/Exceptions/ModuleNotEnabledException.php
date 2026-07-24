<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ProductModule;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ModuleNotEnabledException extends NotFoundHttpException
{
    public function __construct(public readonly ProductModule $module)
    {
        parent::__construct("The {$module->label()} module is not enabled.");
    }
}
