<?php

declare(strict_types=1);

namespace Anklesh\AiPlatform;

use Anklesh\AiPlatform\Contracts\AiTransport;
use Anklesh\AiPlatform\Contracts\ExecutionRecorder;
use Anklesh\AiPlatform\Observability\EventExecutionRecorder;
use Anklesh\AiPlatform\Transports\LaravelAiTransport;
use Illuminate\Support\ServiceProvider;

final class AiPlatformServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/anklesh-ai-platform.php',
            'anklesh-ai-platform',
        );

        $this->app->bind(AiTransport::class, LaravelAiTransport::class);
        $this->app->bind(ExecutionRecorder::class, EventExecutionRecorder::class);
        $this->app->singleton(AiPlatform::class);
        $this->app->alias(AiPlatform::class, 'anklesh-ai-platform');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/anklesh-ai-platform.php' => config_path('anklesh-ai-platform.php'),
        ], 'anklesh-ai-platform-config');
    }
}
