<?php

use App\Providers\AIServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\NumberSeriesServiceProvider;

return [
    AppServiceProvider::class,
    NumberSeriesServiceProvider::class,
    AdminPanelProvider::class,
    AIServiceProvider::class,
];
