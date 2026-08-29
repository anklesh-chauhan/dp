<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform\Facades;

use Anklesh\AiPlatform\AiPlatform;
use Illuminate\Support\Facades\Facade;

/** @see AiPlatform */
final class AnkleshAi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiPlatform::class;
    }
}
