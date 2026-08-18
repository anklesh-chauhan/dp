<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\BatchReleaseStatus;
use App\Domain\QMS\Models\BatchRelease;
use App\Domain\QMS\Services\BatchReleaseTransitionService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Filament\Resources\BatchReleases\BatchReleaseResource;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'Review:BatchRelease',
        'Release:BatchRelease',
        'Manage:BatchRelease',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->creator = User::factory()->create();
    $this->releaser = User::factory()->create();
    $this->releaser->givePermissionTo($this->permissions);
    $this->creator->givePermissionTo($this->permissions);
    $this->release = BatchRelease::factory()->create([
        'created_by' => $this->creator->id,
        'owner_id' => $this->creator->id,
    ]);
});

it('installs the batch release schema and permissions', function (): void {
    expect(Schema::hasColumns('batch_releases', [
        'release_number',
        'batch_number',
        'product_name',
        'status',
        'document_execution_id',
        'disposition_rationale',
        'owner_id',
        'created_by',
        'released_by',
        'released_at',
        'rejected_at',
    ]))->toBeTrue()
        ->and(Schema::hasTable('batch_release_events'))->toBeTrue()
        ->and(QmsModuleSeeder::PERMISSIONS)->toContain(
            'ViewAny:BatchRelease',
            'Review:BatchRelease',
            'Release:BatchRelease',
            'Manage:BatchRelease',
        )
        ->and(BatchReleaseResource::getNavigationGroup())->toBe('QMS')
        ->and(BatchReleaseResource::getNavigationSort())->toBe(26);
});

it('requires an independent quality releaser for signed batch certification', function (): void {
    $service = app(BatchReleaseTransitionService::class);
    $service->transition(
        $this->release,
        BatchReleaseStatus::UnderReview,
        $this->creator,
        'Batch record complete and ready for independent release.',
    );

    expect(fn () => $service->transition(
        $this->release->fresh(),
        BatchReleaseStatus::Released,
        $this->creator,
        'Creator attempted to self-certify.',
    ))->toThrow(ValidationException::class);

    $released = $service->transition(
        $this->release->fresh(),
        BatchReleaseStatus::Released,
        $this->releaser,
        'QP reviewed the batch independently of production.',
        ipAddress: '203.0.113.55',
        userAgent: 'QualiGxP-QMS-Test/1.0',
    );

    $signedEvent = $released->auditEvents()->orderBy('id')->get()->last();

    expect($released->status)->toBe(BatchReleaseStatus::Released)
        ->and($released->released_by)->toBe($this->releaser->id)
        ->and($released->released_at)->not->toBeNull()
        ->and($signedEvent->signatureMeaning())->toBe(BatchReleaseStatus::Released->value)
        ->and(app(ElectronicSignatureVerifier::class)->isValid($signedEvent))->toBeTrue();

    expect(fn () => $signedEvent->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});
