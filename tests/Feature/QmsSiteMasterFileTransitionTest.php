<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Domain\QMS\Services\SiteMasterFileTransitionService;
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
        'Update:SiteMasterFile',
        'Publish:SiteMasterFile',
        'Retire:SiteMasterFile',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo($this->permissions);
    $this->owner = User::factory()->create();
    $this->creator = User::factory()->create();
    $this->file = SiteMasterFile::factory()->create([
        'owner_id' => $this->owner,
        'created_by' => $this->creator,
        'sections' => [
            ...SiteMasterFile::defaultSections(),
            'site_information' => [
                'content' => 'Licensed manufacturing site overview.',
                'document_id' => null,
            ],
        ],
    ]);
});

it('records review publish and retire with signed publish history', function (): void {
    $service = app(SiteMasterFileTransitionService::class);

    $inReview = $service->transition(
        $this->file,
        SiteMasterFileStatus::InReview,
        $this->actor,
        'SMF ready for QA review.',
    );
    $published = $service->transition(
        $inReview,
        SiteMasterFileStatus::Published,
        $this->actor,
        'Independent QA publish of site master file.',
        ipAddress: '203.0.113.55',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );
    $retired = $service->transition(
        $published,
        SiteMasterFileStatus::Retired,
        $this->actor,
        'Superseded by next SMF revision.',
        ipAddress: '203.0.113.55',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );

    $events = $retired->auditEvents()->orderBy('id')->get();
    $publishEvent = $events->get(1);

    expect($retired->status)->toBe(SiteMasterFileStatus::Retired)
        ->and($published->published_by)->toBe($this->actor->id)
        ->and($published->published_at)->not->toBeNull()
        ->and($events)->toHaveCount(3)
        ->and($events->first()->signature_hash)->toBeNull()
        ->and($publishEvent?->signatureMeaning())->toBe(SiteMasterFileStatus::Published->value)
        ->and(app(ElectronicSignatureVerifier::class)->isValid($publishEvent))->toBeTrue();

    expect(fn () => $publishEvent?->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('requires site information and independent publish authority', function (): void {
    $service = app(SiteMasterFileTransitionService::class);

    $this->file->update([
        'sections' => SiteMasterFile::defaultSections(),
        'status' => SiteMasterFileStatus::InReview,
    ]);

    expect(fn () => $service->transition(
        $this->file->fresh(),
        SiteMasterFileStatus::Published,
        $this->actor,
        'Missing site information.',
    ))->toThrow(ValidationException::class);

    $this->file->update([
        'sections' => [
            ...SiteMasterFile::defaultSections(),
            'site_information' => ['content' => 'Site overview present.', 'document_id' => null],
        ],
        'owner_id' => $this->actor->id,
    ]);

    expect(fn () => $service->transition(
        $this->file->fresh(),
        SiteMasterFileStatus::Published,
        $this->actor,
        'Self publish blocked.',
    ))->toThrow(ValidationException::class);
});

it('blocks transitions when qms is disabled or permission is missing', function (): void {
    $service = app(SiteMasterFileTransitionService::class);

    config()->set('modules.enabled', ['dms']);
    expect(fn () => $service->transition(
        $this->file,
        SiteMasterFileStatus::InReview,
        $this->actor,
        'Disabled module.',
    ))->toThrow(ModuleNotEnabledException::class);

    config()->set('modules.enabled', ['dms', 'qms']);
    $unauthorized = User::factory()->create();
    expect(fn () => $service->transition(
        $this->file,
        SiteMasterFileStatus::InReview,
        $unauthorized,
        'No permission.',
    ))->toThrow(AuthorizationException::class);
});
