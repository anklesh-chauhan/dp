<?php

declare(strict_types=1);

use App\Data\SopDocumentData;
use App\Domain\DMS\Actions\CreateDocumentFromTemplateAction;
use App\Domain\DMS\Services\SopGeneratorService;
use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('resolves canonical and legacy generation entry points through the DMS boundary', function (): void {
    $entryPoints = [
        App\Actions\Sop\CreateDocumentFromTemplateAction::class => CreateDocumentFromTemplateAction::class,
        App\Services\Sop\SopGeneratorService::class => SopGeneratorService::class,
    ];

    foreach ($entryPoints as $legacy => $canonical) {
        expect(app($canonical))->toBeInstanceOf($canonical)
            ->and(app($legacy))->toBeInstanceOf($canonical);
    }
});

it('keeps the canonical generation guard for unpublished templates', function (): void {
    TemplateStatus::query()->create([
        'code' => TemplateStatus::DRAFT,
        'name' => 'Draft',
    ]);

    $template = SopTemplate::factory()->create();

    expect(fn () => app(CreateDocumentFromTemplateAction::class)->execute(
        new SopDocumentData(
            templateId: $template->id,
            title: 'Draft Controlled Document',
            ownerId: 1,
            createdBy: 1,
        ),
    ))->toThrow(ValidationException::class, 'Only published templates');
});
