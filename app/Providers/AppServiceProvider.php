<?php

namespace App\Providers;

use App\Models\DocumentType;
use App\Observers\DocumentTypeObserver;
use App\Services\AI\FallbackLLMService;
use App\Services\AI\GeminiService;
use App\Services\AI\LLMServiceInterface;
use App\Services\AI\OllamaService;
use App\Support\Sop\VariableTypes\VariableTypeRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VariableTypeRegistry::class, fn (): VariableTypeRegistry => VariableTypeRegistry::default());

        $this->app->singleton(LLMServiceInterface::class, function ($app) {
            return new FallbackLLMService(
                $app->make(GeminiService::class),
                $app->make(OllamaService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DocumentType::observe(DocumentTypeObserver::class);
    }
}
