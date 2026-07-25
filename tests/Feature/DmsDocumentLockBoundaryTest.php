<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\LockDocumentAction;
use App\Domain\DMS\Actions\UnlockDocumentAction;
use App\Domain\DMS\Services\DocumentLockService;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('resolves canonical and legacy lock entry points through the DMS boundary', function (): void {
    $entryPoints = [
        App\Actions\Sop\LockDocumentAction::class => LockDocumentAction::class,
        App\Actions\Sop\UnlockDocumentAction::class => UnlockDocumentAction::class,
        App\Services\Sop\DocumentLockService::class => DocumentLockService::class,
    ];

    foreach ($entryPoints as $legacy => $canonical) {
        expect(app($canonical))->toBeInstanceOf($canonical)
            ->and(app($legacy))->toBeInstanceOf($canonical);
    }
});

it('keeps the canonical document lock guard behavior', function (): void {
    expect(fn () => app(LockDocumentAction::class)->execute(
        new SopDocument,
        new User,
    ))->toThrow(ValidationException::class, 'not in an editable state');
});

it('keeps unlocking an already unlocked document idempotent', function (): void {
    $document = new SopDocument;

    expect(app(UnlockDocumentAction::class)->execute(
        $document,
        new User,
    ))->toBe($document);
});

it('keeps the canonical template lock guard behavior', function (): void {
    expect(fn () => app(DocumentLockService::class)->lockTemplate(
        new SopTemplate,
        new User,
    ))->toThrow(ValidationException::class, 'Only draft templates');
});
