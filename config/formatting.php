<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default display formats
    |--------------------------------------------------------------------------
    |
    | Used when no Organization Profile override is configured. Filament tables,
    | forms, and infolists consume these through DateFormatSettings.
    |
    */

    'date' => env('DOCUPHARMA_DATE_FORMAT', 'd/m/Y'),
    'datetime' => env('DOCUPHARMA_DATETIME_FORMAT', 'd/m/Y H:i'),
    'time' => env('DOCUPHARMA_TIME_FORMAT', 'H:i'),

];
