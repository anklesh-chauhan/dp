<?php

namespace App\Providers;

use App\Models\DocumentType;
use App\Observers\DocumentTypeObserver;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DocumentType::observe(DocumentTypeObserver::class);
    }
}
