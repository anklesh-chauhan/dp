<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\NumberSeries\NumberSeriesRegistry;
use Illuminate\Support\ServiceProvider;

class NumberSeriesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NumberSeriesRegistry::class, fn (): NumberSeriesRegistry => new NumberSeriesRegistry);
    }
}
