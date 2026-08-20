<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ProductReturnDisposition;
use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Models\ProductReturn;
use App\Domain\QMS\Models\ProductReturnEvent;
use App\Domain\QMS\Services\ProductReturnTransitionService;
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
        'Receive:ProductReturn',
        'Quarantine:ProductReturn',
        'Dispose:ProductReturn',
        'Close:ProductReturn',
        'Manage:ProductReturn',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->return = ProductReturn::factory()->create();
});

it('records an attributable timeline and signs QA disposition decisions', function (): void {
    $service = app(ProductReturnTransitionService::class);
    $service->transition($this->return, ProductReturnStatus::Received, $this->actor, 'Return received.');
    $service->transition($this->return, ProductReturnStatus::UnderQuarantine, $this->actor, 'Moved to quarantine.');
    $disposed = $service->transition(
        $this->return,
        ProductReturnStatus::DispositionPending,
        $this->actor,
        'QA decided to destroy the returned stock.',
        [
            'disposition' => ProductReturnDisposition::Destroy,
            'qa_disposition_notes' => 'Not suitable for rework.',
            'signature' => 'must-not-be-recorded',
        ],
        '203.0.113.60',
        'QualiGxP-QMS-Test/1.0',
    );
    $closed = $service->transition($this->return, ProductReturnStatus::Closed, $this->actor, 'Return closed after disposition.');

    $events = $closed->auditEvents()->orderBy('id')->get();
    $signedDisposition = $events->first(fn (ProductReturnEvent $event): bool => $event->to_status === ProductReturnStatus::DispositionPending);

    expect($disposed->disposition)->toBe(ProductReturnDisposition::Destroy)
        ->and($disposed->qa_disposition_notes)->toBe('Not suitable for rework.')
        ->and($closed->status)->toBe(ProductReturnStatus::Closed)
        ->and($closed->received_at)->not->toBeNull()
        ->and($closed->quarantined_at)->not->toBeNull()
        ->and($closed->dispositioned_at)->not->toBeNull()
        ->and($closed->closed_at)->not->toBeNull()
        ->and($events)->toHaveCount(4)
        ->and($signedDisposition?->signature_hash)->not->toBeNull()
        ->and($signedDisposition?->signatureIpAddress())->toBe('203.0.113.60')
        ->and($signedDisposition?->context)->toMatchArray([
            'disposition' => ProductReturnDisposition::Destroy->value,
            'qa_disposition_notes' => 'Not suitable for rework.',
        ])
        ->and($signedDisposition?->signatureContentDigest())->not->toBeNull()
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signedDisposition))->toBeTrue();

    expect(fn () => $signedDisposition->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('requires a concrete QA disposition before disposition and closure', function (): void {
    $this->return->update(['status' => ProductReturnStatus::UnderQuarantine]);
    $service = app(ProductReturnTransitionService::class);

    expect(fn () => $service->transition(
        $this->return,
        ProductReturnStatus::DispositionPending,
        $this->actor,
        'Disposition without decision.',
    ))->toThrow(ValidationException::class)
        ->and(ProductReturnEvent::query()->count())->toBe(0);
});

it('rejects missing reasons unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(ProductReturnTransitionService::class);

    expect(fn () => $service->transition(
        $this->return,
        ProductReturnStatus::Received,
        $this->actor,
        ' ',
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->transition(
        $this->return,
        ProductReturnStatus::Received,
        User::factory()->create(),
        'Return received.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->transition(
        $this->return,
        ProductReturnStatus::Closed,
        $this->actor,
        'Invalid direct closure.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->return,
        ProductReturnStatus::Received,
        $this->actor,
        'Return received.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(ProductReturnEvent::query()->count())->toBe(0);
});
