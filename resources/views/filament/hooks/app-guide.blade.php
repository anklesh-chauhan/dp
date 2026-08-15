<?php

use App\Support\AppGuide\AppGuide;
use Illuminate\Support\Facades\Auth;

$user = Auth::user();

if ($user === null) {
    return;
}

try {
    $config = app(AppGuide::class)->payload();
} catch (Throwable $exception) {
    report($exception);

    return;
}

$autoStart = ! $user->hasCompletedAppGuide();
?>

<div
    id="qualigxp-app-guide"
    data-auto-start="{{ $autoStart ? '1' : '0' }}"
    hidden
>
    <script type="application/json" id="qualigxp-app-guide-config">
        {!! json_encode($config, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
</div>
