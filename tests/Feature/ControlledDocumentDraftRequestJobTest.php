<?php

declare(strict_types=1);

use App\Jobs\ProcessControlledDocumentDraftRequest;

it('uses retry and timeout settings suitable for long AI generation', function (): void {
    config()->set('ai.draft_queue.timeout', 900);

    $job = new ProcessControlledDocumentDraftRequest(123);

    expect($job->uniqueId())->toBe('123')
        ->and($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([30, 120])
        ->and($job->timeout)->toBe(900)
        ->and($job->uniqueFor)->toBeGreaterThan($job->timeout);
});
