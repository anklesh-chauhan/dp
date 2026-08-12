<?php

declare(strict_types=1);

namespace App\Providers;

use App\Foundation\AI\Validation\Contracts\RepairExecutor;
use App\Foundation\AI\Validation\Contracts\RepairPipeline;
use App\Foundation\AI\Validation\Contracts\RepairService;
use App\Foundation\AI\Validation\Contracts\ValidationEngine;
use App\Foundation\AI\Validation\DefaultValidationEngine;
use App\Foundation\AI\Validation\Pipeline\DefaultRepairPipeline;
use App\Foundation\AI\Validation\Services\DefaultRepairExecutor;
use App\Foundation\AI\Validation\Services\DefaultRepairService;
use App\Services\AI\ApprovalNarrativeAssistant;
use App\Services\AI\Contracts\AiExecutionRecorder;
use App\Services\AI\Contracts\ApprovalNarrativeGenerator;
use App\Services\AI\Contracts\DocumentClassifier;
use App\Services\AI\Contracts\DocumentContentGenerator;
use App\Services\AI\Contracts\DocumentDescriptionGenerator;
use App\Services\AI\Contracts\LLMManagerContract;
use App\Services\AI\Contracts\TemplateGenerator;
use App\Services\AI\DocumentAiClassifier;
use App\Services\AI\DocumentAiDescriptionGenerator;
use App\Services\AI\DocumentContentAssistant;
use App\Services\AI\Observability\DatabaseAiExecutionRecorder;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\Routing\LLMManager;
use App\Services\AI\Routing\ProviderRegistry;
use App\Services\AI\TemplateGeneratorService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

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
                $registry = new ProviderRegistry;

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
            ValidationEngine::class,
            DefaultValidationEngine::class,
        );

        $this->app->bind(
            RepairPipeline::class,
            DefaultRepairPipeline::class,
        );

        $this->app->bind(
            RepairService::class,
            DefaultRepairService::class,
        );

        $this->app->bind(
            RepairExecutor::class,
            DefaultRepairExecutor::class,
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
            ApprovalNarrativeGenerator::class,
            ApprovalNarrativeAssistant::class,
        );

        $this->app->bind(
            DocumentContentGenerator::class,
            DocumentContentAssistant::class,
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
