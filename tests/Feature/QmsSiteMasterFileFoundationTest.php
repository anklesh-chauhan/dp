<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the site master file schema', function (): void {
    expect(Schema::hasColumns('site_master_files', [
        'smf_number',
        'title',
        'status',
        'version',
        'sections',
        'owner_id',
        'created_by',
        'published_by',
        'published_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('site_master_file_events', [
            'event_uuid',
            'site_master_file_id',
            'from_status',
            'to_status',
            'actor_id',
            'reason',
            'context',
            'signature_hash',
            'signature_ip_address',
            'signature_user_agent',
            'occurred_at',
        ]))->toBeTrue();
});

it('persists site master file sections and identity', function (): void {
    $owner = User::factory()->create();
    $creator = User::factory()->create();

    $file = SiteMasterFile::factory()->create([
        'title' => 'Plant A Site Master File',
        'owner_id' => $owner,
        'created_by' => $creator,
        'status' => SiteMasterFileStatus::Draft,
        'sections' => [
            ...SiteMasterFile::defaultSections(),
            'site_information' => [
                'content' => 'Manufacturing site at Example Road.',
                'document_id' => null,
            ],
        ],
    ]);

    expect($file->smf_number)->toStartWith('SMF-')
        ->and($file->version)->toBe(1)
        ->and($file->sections['site_information']['content'])->toBe('Manufacturing site at Example Road.')
        ->and(array_keys($file->sections))->toContain('pharmaceutical_quality_system');
});

it('registers site master file permissions in the qms module seeder', function (): void {
    $this->seed(QmsModuleSeeder::class);

    expect(QmsModuleSeeder::PERMISSIONS)->toContain('Publish:SiteMasterFile')
        ->and(QmsModuleSeeder::PERMISSIONS)->toContain('Retire:SiteMasterFile');
});
