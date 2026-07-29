<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\ArchiveDocumentAction;
use App\Domain\DMS\Actions\ArchiveTemplateAction;
use App\Domain\DMS\Actions\CompleteDocumentRetentionAction;
use App\Domain\DMS\Actions\CompleteTemplateRetentionAction;
use App\Domain\DMS\Actions\DestroyDocumentAction;
use App\Domain\DMS\Actions\DestroyTemplateAction;
use App\Domain\DMS\Actions\MarkDocumentObsoleteAction;
use App\Domain\DMS\Actions\MarkTemplateObsoleteAction;
use App\Domain\DMS\Services\RetentionLifecycleService;
use App\Models\ControlledDocument;
use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('resolves canonical and legacy retention entry points through the DMS boundary', function (): void {
    $entryPoints = [
        App\Actions\Sop\ArchiveDocumentAction::class => ArchiveDocumentAction::class,
        App\Actions\Sop\ArchiveTemplateAction::class => ArchiveTemplateAction::class,
        App\Actions\Sop\CompleteDocumentRetentionAction::class => CompleteDocumentRetentionAction::class,
        App\Actions\Sop\CompleteTemplateRetentionAction::class => CompleteTemplateRetentionAction::class,
        App\Actions\Sop\DestroyDocumentAction::class => DestroyDocumentAction::class,
        App\Actions\Sop\DestroyTemplateAction::class => DestroyTemplateAction::class,
        App\Actions\Sop\MarkDocumentObsoleteAction::class => MarkDocumentObsoleteAction::class,
        App\Actions\Sop\MarkTemplateObsoleteAction::class => MarkTemplateObsoleteAction::class,
        App\Services\Sop\RetentionLifecycleService::class => RetentionLifecycleService::class,
    ];

    foreach ($entryPoints as $legacy => $canonical) {
        expect(app($canonical))->toBeInstanceOf($canonical)
            ->and(app($legacy))->toBeInstanceOf($canonical);
    }
});

it('keeps the canonical document retention guard behavior', function (): void {
    expect(fn () => app(MarkDocumentObsoleteAction::class)->execute(
        new ControlledDocument,
        new User,
    ))->toThrow(ValidationException::class, 'Only effective or approved documents');
});

it('keeps the canonical template retention guard behavior', function (): void {
    expect(fn () => app(MarkTemplateObsoleteAction::class)->execute(
        new DocumentTemplate,
        new User,
    ))->toThrow(ValidationException::class, 'Only published templates');
});
