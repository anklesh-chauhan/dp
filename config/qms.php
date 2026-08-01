<?php

declare(strict_types=1);

return [
    'draft_autosave' => [
        'enabled' => (bool) env('QMS_DRAFT_AUTOSAVE_ENABLED', true),
        'interval_seconds' => max(1, (int) env('QMS_DRAFT_AUTOSAVE_INTERVAL_SECONDS', 5)),
    ],
];
