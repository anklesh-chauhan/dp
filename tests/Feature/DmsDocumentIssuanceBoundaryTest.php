<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\DestroyIssuanceAction;
use App\Domain\DMS\Actions\IssueDocumentAction;
use App\Domain\DMS\Actions\RecallIssuanceAction;
use App\Domain\DMS\Services\DocumentIssuanceService;
use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\IssuanceStatus;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('resolves canonical and legacy issuance entry points through the DMS boundary', function (): void {
    expect(app(IssueDocumentAction::class))
        ->toBeInstanceOf(IssueDocumentAction::class)
        ->and(app(RecallIssuanceAction::class))
        ->toBeInstanceOf(RecallIssuanceAction::class)
        ->and(app(DestroyIssuanceAction::class))
        ->toBeInstanceOf(DestroyIssuanceAction::class)
        ->and(app(DocumentIssuanceService::class))
        ->toBeInstanceOf(DocumentIssuanceService::class)
        ->and(app(App\Actions\Sop\IssueDocumentAction::class))
        ->toBeInstanceOf(IssueDocumentAction::class)
        ->and(app(App\Actions\Sop\RecallIssuanceAction::class))
        ->toBeInstanceOf(RecallIssuanceAction::class)
        ->and(app(App\Actions\Sop\DestroyIssuanceAction::class))
        ->toBeInstanceOf(DestroyIssuanceAction::class)
        ->and(app(App\Services\Sop\DocumentIssuanceService::class))
        ->toBeInstanceOf(DocumentIssuanceService::class);
});

it('keeps the canonical issuance guard behavior', function (): void {
    expect(fn () => app(IssueDocumentAction::class)->execute(
        new ControlledDocument,
        new User,
    ))->toThrow(ValidationException::class, 'Only effective log documents');
});

it('keeps the canonical recall guard behavior', function (): void {
    expect(fn () => app(RecallIssuanceAction::class)->execute(
        new DocumentIssuance,
        new User,
        'Routine recall.',
    ))->toThrow(ValidationException::class, 'Only active controlled copies');
});

it('keeps the canonical destruction guard behavior', function (): void {
    $issuance = new DocumentIssuance;
    $issuance->setRelation('issuanceStatus', new IssuanceStatus([
        'code' => IssuanceStatus::DESTROYED,
        'name' => 'Destroyed',
    ]));

    expect(fn () => app(DestroyIssuanceAction::class)->execute(
        $issuance,
        new User,
        'Duplicate destruction request.',
    ))->toThrow(ValidationException::class, 'already been destroyed');
});
