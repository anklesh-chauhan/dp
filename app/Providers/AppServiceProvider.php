<?php

namespace App\Providers;

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
        //
    }
}
