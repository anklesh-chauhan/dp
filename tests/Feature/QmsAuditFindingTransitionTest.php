<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\AuditFindingEvent;
use App\Domain\QMS\Services\AuditFindingTransitionService;
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
        'Respond:AuditFinding',
        'Verify:AuditFinding',
        'Close:AuditFinding',
        'Manage:AuditFinding',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->finding = AuditFinding::factory()->create();
});

it('records response verification and closure with signed append-only history', function (): void {
    $service = app(AuditFindingTransitionService::class);
    $service->transition(
        $this->finding,
        AuditFindingDisposition::ResponsePending,
        $this->actor,
        'Department response requested.',
    );
    $submitted = $service->transition(
        $this->finding,
        AuditFindingDisposition::UnderVerification,
        $this->actor,
        'Corrective response submitted.',
        response: 'Procedure revised and affected personnel retrained.',
        ipAddress: '203.0.113.61',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );
    $accepted = $service->transition(
        $submitted,
        AuditFindingDisposition::Accepted,
        $this->actor,
        'Response independently verified.',
        verificationNotes: 'Revised procedure and training records were sampled and found effective.',
    );
    $closed = $service->transition(
        $accepted,
        AuditFindingDisposition::Closed,
        $this->actor,
        'Finding formally closed.',
    );

    $events = $closed->auditEvents()->orderBy('id')->get();
    $responseEvent = $events->get(1);

    expect($closed->disposition)->toBe(AuditFindingDisposition::Closed)
        ->and($closed->response)->toBe('Procedure revised and affected personnel retrained.')
        ->and($closed->responded_at)->not->toBeNull()
        ->and($closed->verified_by)->toBe($this->actor->id)
        ->and($closed->verified_at)->not->toBeNull()
        ->and($closed->closed_at)->not->toBeNull()
        ->and($events)->toHaveCount(4)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($responseEvent?->signatureMeaning())->toBe(AuditFindingDisposition::UnderVerification->value)
        ->and($responseEvent?->signatureIpAddress())->toBe('203.0.113.61')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($responseEvent))->toBeTrue();

    expect(fn () => $responseEvent?->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('requires response evidence and independent verification', function (): void {
    $service = app(AuditFindingTransitionService::class);
    $this->finding->update(['disposition' => AuditFindingDisposition::ResponsePending]);

    expect(fn () => $service->transition(
        $this->finding,
        AuditFindingDisposition::UnderVerification,
        $this->actor,
        'No response supplied.',
    ))->toThrow(ValidationException::class);

    $this->finding->update([
        'disposition' => AuditFindingDisposition::UnderVerification,
        'response' => 'Owner response.',
        'responded_at' => now(),
        'owner_id' => $this->actor->id,
    ]);

    expect(fn () => $service->transition(
        $this->finding,
        AuditFindingDisposition::Accepted,
        $this->actor,
        'Self-verification attempted.',
        verificationNotes: 'Owner attempted verification.',
    ))->toThrow(ValidationException::class)
        ->and(AuditFindingEvent::query()->count())->toBe(0);
});

it('preserves rejected verification evidence in the rework event', function (): void {
    $this->finding->update([
        'disposition' => AuditFindingDisposition::UnderVerification,
        'response' => 'Initial response.',
        'responded_at' => now(),
        'verification_notes' => 'Evidence was incomplete.',
    ]);

    $rework = app(AuditFindingTransitionService::class)->transition(
        $this->finding,
        AuditFindingDisposition::ResponsePending,
        $this->actor,
        'Additional evidence required.',
    );

    expect($rework->verification_notes)->toBeNull()
        ->and($rework->auditEvents()->sole()->context)
        ->toBe(['verification_notes' => 'Evidence was incomplete.']);
});

it('rejects unauthorized invalid and disabled transitions without events', function (): void {
    $service = app(AuditFindingTransitionService::class);

    expect(fn () => $service->transition(
        $this->finding,
        AuditFindingDisposition::ResponsePending,
        User::factory()->create(),
        'Unauthorized.',
    ))->toThrow(AuthorizationException::class);

    expect(fn () => $service->transition(
        $this->finding,
        AuditFindingDisposition::Closed,
        $this->actor,
        'Invalid direct closure.',
    ))->toThrow(ValidationException::class);

    config()->set('modules.enabled', ['dms']);

    expect(fn () => $service->transition(
        $this->finding,
        AuditFindingDisposition::ResponsePending,
        $this->actor,
        'Disabled.',
    ))->toThrow(ModuleNotEnabledException::class)
        ->and(AuditFindingEvent::query()->count())->toBe(0);
});
