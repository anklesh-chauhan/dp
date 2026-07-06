<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Padding Overflow Behavior
    |--------------------------------------------------------------------------
    |
    | When a counter exceeds its configured padding width, this setting controls
    | whether the series expands dynamically ("expand") or throws an exception
    | ("throw").
    |
    */

    'overflow_behavior' => env('NUMBER_SERIES_OVERFLOW_BEHAVIOR', 'expand'),

    /*
    |--------------------------------------------------------------------------
    | Default Number Series
    |--------------------------------------------------------------------------
    |
    | Applied to every document type unless overridden below. Placeholders:
    |   {type}       - uppercase document type code (e.g. SOP, LOG)
    |   {department} - uppercase department code (e.g. QA, PROD)
    |
    */

    'default' => [
        'prefix_pattern' => '{type}-{department}-',
        'padding_length' => 5,
        'suffix' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Type Overrides
    |--------------------------------------------------------------------------
    |
    | Optional per-type settings merged on top of the default configuration.
    | Any document type without an entry here still uses the default pattern.
    |
    */

    'types' => [
        // 'BMR' => [
        //     'prefix_pattern' => 'BMR-{department}-',
        //     'padding_length' => 4,
        // ],
    ],

];
