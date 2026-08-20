<?php

declare(strict_types=1);

use App\Domain\Shared\Contracts\PostgresDumpRunner;
use App\Domain\Shared\Contracts\PostgresRestoreRunner;
use App\Domain\Shared\Enums\SecurityAuditEventType;
use App\Domain\Shared\Exceptions\SystemBackupException;
use App\Domain\Shared\Services\SystemBackupService;
use App\Filament\Pages\ManageSystemBackups;
use App\Jobs\CreateSystemBackupJob;
use App\Jobs\RestoreSystemBackupJob;
use App\Models\SecurityAuditEvent;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Support\FakePostgresDumpRunner;
use Tests\Support\FakePostgresRestoreRunner;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->backupRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gxp-backup-tests-'.bin2hex(random_bytes(4));

    foreach (['backups', 'private', 'public'] as $directory) {
        mkdir($this->backupRoot.DIRECTORY_SEPARATOR.$directory, 0755, true);
    }

    config()->set('filesystems.disks.backups.root', $this->backupRoot.DIRECTORY_SEPARATOR.'backups');
    config()->set('filesystems.disks.local.root', $this->backupRoot.DIRECTORY_SEPARATOR.'private');
    config()->set('filesystems.disks.public.root', $this->backupRoot.DIRECTORY_SEPARATOR.'public');
    config()->set('gxp.backup.retain_count', 12);
    config()->set('gxp.backup.disks', ['local', 'public']);

    app('filesystem')->forgetDisk('backups');
    app('filesystem')->forgetDisk('local');
    app('filesystem')->forgetDisk('public');

    $this->restoreRunner = new FakePostgresRestoreRunner;
    app()->instance(PostgresDumpRunner::class, new FakePostgresDumpRunner);
    app()->instance(PostgresRestoreRunner::class, $this->restoreRunner);

    Storage::disk('local')->put('sop.txt', 'controlled procedure');
    Storage::disk('public')->put('logo.txt', 'site logo');
    Cache::flush();
});

afterEach(function (): void {
    if (isset($this->backupRoot) && is_dir($this->backupRoot)) {
        File::deleteDirectory($this->backupRoot);
    }
});

it('creates an integrity-checked archive of the database dump and file disks', function (): void {
    $actor = User::factory()->create();
    $manifest = app(SystemBackupService::class)->create($actor, 'Manual backup for OQ.');

    expect($manifest['backup_uuid'])->not->toBeEmpty()
        ->and($manifest['created_by'])->toBe($actor->email)
        ->and($manifest['database_sha256'])->toHaveLength(64)
        ->and($manifest['files_sha256'])->toHaveLength(64)
        ->and(Storage::disk('backups')->exists($manifest['backup_uuid'].'/manifest.json'))->toBeTrue()
        ->and(Storage::disk('backups')->exists($manifest['backup_uuid'].'/database.dump'))->toBeTrue()
        ->and(Storage::disk('backups')->exists($manifest['backup_uuid'].'/files.zip'))->toBeTrue()
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::BackupCreated)->count())->toBe(1);

    app(SystemBackupService::class)->verify($manifest['backup_uuid']);
});

it('prunes archives older than the configured retain count', function (): void {
    config()->set('gxp.backup.retain_count', 2);

    $service = app(SystemBackupService::class);
    $this->travel(-2)->minutes();
    $first = $service->create(null, 'First.');
    $this->travel(1)->minutes();
    $service->create(null, 'Second.');
    $this->travel(1)->minutes();
    $service->create(null, 'Third.');

    expect($service->list())->toHaveCount(2)
        ->and(Storage::disk('backups')->exists($first['backup_uuid'].'/manifest.json'))->toBeFalse();
});

it('rejects restore when the stored dump checksum has been tampered', function (): void {
    $service = app(SystemBackupService::class);
    $manifest = $service->create(null, 'Integrity check.');
    Storage::disk('local')->put('sop.txt', 'changed after backup');
    Storage::disk('backups')->put($manifest['backup_uuid'].'/database.dump', 'TAMPERED');

    expect(fn () => $service->restore($manifest['backup_uuid'], User::factory()->create(), 'Attempted restore.'))
        ->toThrow(SystemBackupException::class)
        ->and($this->restoreRunner->called)->toBeFalse()
        ->and(Storage::disk('local')->get('sop.txt'))->toBe('changed after backup')
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::BackupRestoreFailed)->count())->toBe(1)
        ->and(Storage::disk('backups')->get('restore.log'))->toContain('failed');
});

it('restores files, records a surviving restore log, and writes a forward audit event', function (): void {
    $actor = User::factory()->create();
    $service = app(SystemBackupService::class);
    $manifest = $service->create($actor, 'Before change.');

    Storage::disk('local')->put('sop.txt', 'changed after backup');

    $service->restore($manifest['backup_uuid'], $actor, 'Restore after test change.');

    expect($this->restoreRunner->called)->toBeTrue()
        ->and(Storage::disk('local')->get('sop.txt'))->toBe('controlled procedure')
        ->and(Storage::disk('public')->get('logo.txt'))->toBe('site logo')
        ->and(Storage::disk('backups')->get('restore.log'))->toContain('restored')
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::BackupRestored)->count())->toBe(1);
});

it('does not replace files when the restore runner fails', function (): void {
    $this->restoreRunner->throw = new SystemBackupException('Runner failed.');
    $actor = User::factory()->create();
    $service = app(SystemBackupService::class);
    $manifest = $service->create($actor, 'Before failure.');

    Storage::disk('local')->put('sop.txt', 'changed after backup');

    expect(fn () => $service->restore($manifest['backup_uuid'], $actor, 'Restore that cannot complete.'))
        ->toThrow(SystemBackupException::class)
        ->and(Storage::disk('local')->get('sop.txt'))->toBe('changed after backup')
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::BackupRestoreFailed)->count())->toBe(1);
});

it('writes a downloadable archive and records a download audit event', function (): void {
    $actor = User::factory()->create();
    $manifest = app(SystemBackupService::class)->create($actor, 'Downloadable archive.');
    $path = $this->backupRoot.DIRECTORY_SEPARATOR.'download.zip';

    app(SystemBackupService::class)->writeDownloadZip($manifest['backup_uuid'], $path, $actor);

    expect(is_file($path))->toBeTrue()
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::BackupDownloaded)->count())->toBe(1);
});

it('creates an archive from the scheduled artisan command', function (): void {
    $this->artisan('gxp:backup-create', ['--sync' => true])
        ->assertSuccessful();

    expect(app(SystemBackupService::class)->list())->toHaveCount(1)
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::BackupCreated)->first()?->reason)
        ->toBe('Scheduled backup.');
});

it('queues a backup from artisan without writing the archive in the command process', function (): void {
    Queue::fake();

    $this->artisan('gxp:backup-create')
        ->assertSuccessful();

    Queue::assertPushed(CreateSystemBackupJob::class, function (CreateSystemBackupJob $job): bool {
        return $job->actorId === null && $job->reason === 'Scheduled backup.';
    });
    expect(app(SystemBackupService::class)->list())->toBeEmpty();
});

it('queues a backup from the system backup page', function (): void {
    Queue::fake();

    foreach (['ViewAny:SystemBackup', 'Create:SystemBackup'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:SystemBackup', 'Create:SystemBackup']);
    $this->actingAs($user);

    Livewire::test(ManageSystemBackups::class)
        ->callAction('createBackup', ['reason' => 'Signed-in backup from the panel.'])
        ->assertNotified();

    Queue::assertPushed(CreateSystemBackupJob::class, function (CreateSystemBackupJob $job) use ($user): bool {
        return $job->actorId === $user->id && $job->reason === 'Signed-in backup from the panel.';
    });
    expect(app(SystemBackupService::class)->list())->toBeEmpty();
});

it('creates an archive from the queued backup job', function (): void {
    $actor = User::factory()->create();

    (new CreateSystemBackupJob($actor->id, 'Queued backup for OQ.'))
        ->handle(app(SystemBackupService::class));

    expect(app(SystemBackupService::class)->list())->toHaveCount(1)
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::BackupCreated)->first()?->reason)
        ->toBe('Queued backup for OQ.');
});

it('allows unsigned cli restore only with force in testing', function (): void {
    $manifest = app(SystemBackupService::class)->create(null, 'Cli restore fixture.');

    $this->artisan('gxp:backup-restore', ['uuid' => $manifest['backup_uuid'], '--reason' => 'Ops restore.'])
        ->assertFailed();

    $this->artisan('gxp:backup-restore', [
        'uuid' => $manifest['backup_uuid'],
        '--reason' => 'Ops restore.',
        '--force' => true,
    ])->assertSuccessful();

    expect($this->restoreRunner->called)->toBeTrue();
});

it('restores from artisan when a signed panel actor is supplied', function (): void {
    $actor = User::factory()->create();
    $manifest = app(SystemBackupService::class)->create($actor, 'Panel restore fixture.');

    $this->artisan('gxp:backup-restore', [
        'uuid' => $manifest['backup_uuid'],
        '--reason' => 'Signed restore from the panel.',
        '--actor' => $actor->id,
    ])->assertSuccessful();

    expect($this->restoreRunner->called)->toBeTrue()
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::BackupRestored)->first()?->actor_id)
        ->toBe($actor->id);
});

it('schedules a daily system backup', function (): void {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('gxp:backup-create')
        ->assertSuccessful();
});

it('hides create and restore actions without those permissions', function (): void {
    Permission::findOrCreate('ViewAny:SystemBackup', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('ViewAny:SystemBackup');
    $this->actingAs($user);

    $manifest = app(SystemBackupService::class)->create(null, 'Visible archive.');

    Livewire::test(ManageSystemBackups::class)
        ->assertOk()
        ->assertActionHidden('createBackup')
        ->assertActionHidden(TestAction::make('download')->table($manifest['backup_uuid']))
        ->assertActionHidden(TestAction::make('restore')->table($manifest['backup_uuid']));
});

it('denies the system backup page without permission', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ManageSystemBackups::class)
        ->assertForbidden();
});

it('queues a restore from the system backup page', function (): void {
    Queue::fake();

    foreach (['ViewAny:SystemBackup', 'Restore:SystemBackup'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:SystemBackup', 'Restore:SystemBackup']);
    $this->actingAs($user);

    $manifest = app(SystemBackupService::class)->create($user, 'Restore queue fixture.');

    Livewire::test(ManageSystemBackups::class)
        ->callAction(TestAction::make('restore')->table($manifest['backup_uuid']), [
            'reason' => 'Signed restore from the panel.',
            'signature_password' => 'password',
        ])
        ->assertNotified();

    Queue::assertPushed(RestoreSystemBackupJob::class, function (RestoreSystemBackupJob $job) use ($manifest, $user): bool {
        return $job->uuid === $manifest['backup_uuid']
            && $job->actorId === $user->id
            && $job->reason === 'Signed restore from the panel.';
    });
    expect($this->restoreRunner->called)->toBeFalse();
});

it('creates and restores a backup from the system backup page', function (): void {
    foreach (['ViewAny:SystemBackup', 'Create:SystemBackup', 'Restore:SystemBackup'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:SystemBackup', 'Create:SystemBackup', 'Restore:SystemBackup']);
    $this->actingAs($user);

    Livewire::test(ManageSystemBackups::class)
        ->callAction('createBackup', ['reason' => 'Signed-in backup from the panel.'])
        ->assertNotified();

    $uuid = app(SystemBackupService::class)->list()[0]['backup_uuid'];
    Storage::disk('local')->put('sop.txt', 'changed after panel backup');

    Livewire::test(ManageSystemBackups::class)
        ->callAction(TestAction::make('restore')->table($uuid), [
            'reason' => 'Signed restore from the panel.',
            'signature_password' => 'password',
        ])
        ->assertNotified();

    expect($this->restoreRunner->called)->toBeTrue()
        ->and(Storage::disk('local')->get('sop.txt'))->toBe('controlled procedure')
        ->and(SecurityAuditEvent::query()->where('event_type', SecurityAuditEventType::BackupRestored)->count())->toBe(1);
});
