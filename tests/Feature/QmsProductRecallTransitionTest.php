<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductRecallClassification;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Models\ProductRecall;
use App\Domain\QMS\Models\ProductRecallEvent;
use App\Domain\QMS\Services\ProductRecallTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'Initiate:ProductRecall',
        'Classify:ProductRecall',
        'Notify:ProductRecall',
        'Execute:ProductRecall',
        'Verify:ProductRecall',
        'Close:ProductRecall',
        'Manage:ProductRecall',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->recall = ProductRecall::factory()->create();
});

it('records an attributable timeline and signs consequential product recall decisions', function (): void {
    $service = app(ProductRecallTransitionService::class);
    $service->transition($this->recall, ProductRecallStatus::Initiated, $this->actor, 'Recall initiated.');
    $service->transition(
        $this->recall,
        ProductRecallStatus::RiskClassified,
        $this->actor,
        'Classified as Class II.',
        ['classification' => ProductRecallClassification::ClassIi],
    );
    $service->transition($this->recall, ProductRecallStatus::NotificationInProgress, $this->actor, 'Stakeholders notified.');
    $service->transition($this->recall, ProductRecallStatus::ExecutionInProgress, $this->actor, 'Retrieval underway.');
    $service->transition(
        $this->recall,
        ProductRecallStatus::EffectivenessCheck,
        $this->actor,
        'Effectiveness verified.',
        ['effectiveness_summary' => 'All distributed packs accounted for.'],
    );
    $closed = $service->transition(
        $this->recall,
        ProductRecallStatus::Closed,
        $this->actor,
        'Recall closed after effectiveness check.',
        ['signature' => 'must-not-be-recorded', 'channel' => 'qa'],
        '203.0.113.50',
        'QualiGxP-QMS-Test/1.0',
    );

    $events = $closed->auditEvents()->orderBy('id')->get();
    $signedClose = $events->last();
    $signedClassify = $events->first(fn (ProductRecallEvent $event): bool => $event->to_status === ProductRecallStatus::RiskClassified);

    expect($closed->status)->toBe(ProductRecallStatus::Closed)
        ->and($closed->classification)->toBe(ProductRecallClassification::ClassIi)
        ->and($closed->initiated_at)->not->toBeNull()
        ->and($closed->classified_at)->not->toBeNull()
        ->and($closed->effectiveness_summary)->toBe('All distributed packs accounted for.')
        ->and($closed->closed_at)->not->toBeNull()
        ->and($events)->toHaveCount(6)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($signedClassify?->signature_hash)->not->toBeNull()
        ->and($signedClose->signatureMeaning())->toBe(ProductRecallStatus::Closed->value)
        ->and($signedClose->signatureSignerId())->toBe($this->actor->id)
        ->and($signedClose->signatureIpAddress())->toBe('203.0.113.50')
        ->and($signedClose->context)->toMatchArray(['channel' => 'qa'])
        ->and($signedClose->signatureContentDigest())->not->toBeNull()
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signedClose))->toBeTrue();

    expect(fn () => $signedClose->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('requires classification and effectiveness evidence for gated transitions', function (): void {
    $this->recall->update(['status' => ProductRecallStatus::Initiated]);
    $service = app(ProductRecallTransitionService::class);

    expect(fn () => $service->transition(
        $this->recall,
        ProductRecallStatus::RiskClassified,
        $this->actor,
        'Missing classification.',
    ))->toThrow(ValidationException::class);

    $this->recall->update(['status' => ProductRecallStatus::ExecutionInProgress]);

    expect(fn () => $service->transition(
        $this->recall,
        ProductRecallStatus::EffectivenessCheck,
        $this->actor,
        'Missing effectiveness summary.',
    ))->toThrow(ValidationException::class)
        ->and(ProductRecallEvent::query()->count())->toBe(0);
});

it('rejects missing reasons unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(ProductRecallTransitionService::class);

    expect(fn () => $service->transition(
        $this->recall,
        ProductRecallStatus::Initiated,
        $this->actor,
        ' ',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        $this->recall,
        ProductRecallStatus::Initiated,
        User::factory()->create(),
        'Recall initiated.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->transition(
        $this->recall,
        ProductRecallStatus::Closed,
        $this->actor,
        'Invalid direct closure.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->recall,
        ProductRecallStatus::Initiated,
        $this->actor,
        'Recall initiated.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(ProductRecallEvent::query()->count())->toBe(0);
});
