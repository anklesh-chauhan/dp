<?php

declare(strict_types=1);

arch('Foundation AI classes are final')
    ->expect('App\Foundation\AI')
    ->classes()
    ->toBeFinal()
    ->ignoring([
        'App\Foundation\AI\Contracts',
    ]);

arch('Foundation AI value objects are readonly')
    ->expect('App\Foundation\AI\Validation\ValueObjects')
    ->classes()
    ->toBeReadonly();
