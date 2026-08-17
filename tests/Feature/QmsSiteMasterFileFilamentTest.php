<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Filament\Resources\SiteMasterFiles\Pages\ListSiteMasterFiles;
use App\Filament\Resources\SiteMasterFiles\Pages\ViewSiteMasterFile;
use App\Filament\Resources\SiteMasterFiles\SiteMasterFileResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:SiteMasterFile',
        'View:SiteMasterFile',
        'Create:SiteMasterFile',
        'Update:SiteMasterFile',
        'Review:SiteMasterFile',
        'Publish:SiteMasterFile',
        'Retire:SiteMasterFile',
        'Manage:SiteMasterFile',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces qms entitlement for the site master file resource', function (): void {
    expect(SiteMasterFileResource::canAccess())->toBeTrue()
        ->and(SiteMasterFileResource::getNavigationGroup())->toBe('QMS')
        ->and(SiteMasterFileResource::getNavigationSort())->toBe(19);

    config()->set('modules.enabled', ['dms']);

    expect(SiteMasterFileResource::canAccess())->toBeFalse()
        ->and(SiteMasterFileResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(SiteMasterFileResource::getUrl())->assertForbidden();
});

it('denies direct livewire access without permissions', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListSiteMasterFiles::class)->assertForbidden();
});

it('delegates submit for review through the transition service', function (): void {
    $file = SiteMasterFile::factory()->create([
        'status' => SiteMasterFileStatus::Draft,
        'sections' => [
            ...SiteMasterFile::defaultSections(),
            'site_information' => ['content' => 'Site overview.', 'document_id' => null],
        ],
    ]);

    Livewire::test(ViewSiteMasterFile::class, ['record' => $file->id])
        ->callAction('submitReview', ['reason' => 'Site master file submitted for QA review.'])
        ->assertNotified();

    expect($file->fresh()?->status)->toBe(SiteMasterFileStatus::InReview)
        ->and($file->auditEvents()->count())->toBe(1);
});
