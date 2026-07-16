<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\AI\Contracts\DocumentClassifier;
use App\Services\AI\Contracts\DocumentDescriptionGenerator;
use App\Services\AI\DocumentAiClassifier;
use App\Services\AI\DocumentAiDescriptionGenerator;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\Routing\ProviderRegistry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Routing\LLMManager;
use App\Services\AI\Contracts\TemplateGenerator;
use App\Services\AI\TemplateGeneratorService;
use App\Services\AI\Contracts\AiExecutionRecorder;
use App\Services\AI\Observability\DatabaseAiExecutionRecorder;

final class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerProviders();
        $this->registerDomainServices();
    }

    private function registerProviders(): void
    {
        $this->app->singleton(
            ProviderRegistry::class,
            function (Application $app): ProviderRegistry {
                $registry = new ProviderRegistry();

                foreach ($this->providerClasses() as $providerClass) {
                    $provider = $app->make($providerClass);

                    if (! config(
                        "ai.providers.{$provider->name()}.enabled",
                        false,
                    )) {
                        continue;
                    }

                    $registry->register($provider);
                }

                return $registry;
            },
        );
    }

    private function registerDomainServices(): void
    {
        $this->app->bind(
            \App\Foundation\AI\Validation\Contracts\RepairPipeline::class,
            \App\Foundation\AI\Validation\Pipeline\DefaultRepairPipeline::class,
        );

        $this->app->bind(
            \App\Foundation\AI\Validation\Contracts\RepairService::class,
            \App\Foundation\AI\Validation\Services\DefaultRepairService::class,
        );

        $this->app->bind(
            \App\Foundation\AI\Validation\Contracts\RepairExecutor::class,
            \App\Foundation\AI\Validation\Services\DefaultRepairExecutor::class,
        );

        $this->app->bind(
            DocumentDescriptionGenerator::class,
            DocumentAiDescriptionGenerator::class,
        );

        $this->app->bind(
            DocumentClassifier::class,
            DocumentAiClassifier::class,
        );

        $this->app->bind(
            LLMManagerContract::class,
            LLMManager::class,
        );

        $this->app->bind(
            TemplateGenerator::class,
            TemplateGeneratorService::class,
        );

        $this->app->bind(
            AiExecutionRecorder::class,
            DatabaseAiExecutionRecorder::class,
        );

    }

    /**
     * @return array<class-string>
     */
    private function providerClasses(): array
    {
        return [
            GeminiProvider::class,
            OllamaProvider::class,
        ];
    }
}
