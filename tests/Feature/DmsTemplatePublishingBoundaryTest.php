<?php

declare(strict_types=1);

use App\Domain\DMS\Actions\PublishTemplateAction;
use App\Domain\DMS\Services\TemplatePublisherService;
use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('resolves canonical and legacy publishing entry points through the DMS boundary', function (): void {
    $entryPoints = [
        App\Actions\Sop\PublishTemplateAction::class => PublishTemplateAction::class,
        App\Services\Sop\TemplatePublisherService::class => TemplatePublisherService::class,
    ];

    foreach ($entryPoints as $legacy => $canonical) {
        expect(app($canonical))->toBeInstanceOf($canonical)
            ->and(app($legacy))->toBeInstanceOf($canonical);
    }
});

it('keeps the canonical publishing guard for templates without a draft version', function (): void {
    TemplateStatus::query()->create([
        'code' => TemplateStatus::DRAFT,
        'name' => 'Draft',
    ]);

    $template = SopTemplate::factory()->create();

    expect(fn () => app(PublishTemplateAction::class)->execute(
        $template,
        1,
    ))->toThrow(ValidationException::class, 'Create a draft template version');
});
