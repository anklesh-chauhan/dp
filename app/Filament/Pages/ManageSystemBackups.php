<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Shared\Services\ElectronicSignatureAuthenticator;
use App\Domain\Shared\Services\SystemBackupService;
use App\Jobs\CreateSystemBackupJob;
use App\Jobs\RestoreSystemBackupJob;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

class ManageSystemBackups extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Core · Identity & Access';

    protected static ?string $navigationLabel = 'System backup';

    protected static ?string $title = 'System backup';

    protected static ?int $navigationSort = 80;

    protected static ?string $slug = 'system-backup';

    protected string $view = 'filament.pages.manage-system-backups';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:SystemBackup');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public function getSubheading(): ?string
    {
        return 'QualiGxP can write integrity-checked archives of the database and private/public files. This supports recoverability; the site still owns off-site copies, restore drills, and CSV OQ evidence. Restore replaces this instance and signs you out.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBackup')
                ->label('Create backup')
                ->icon(Heroicon::Plus)
                ->authorize(fn (): bool => (bool) auth()->user()?->can('Create:SystemBackup'))
                ->visible(fn (): bool => (bool) auth()->user()?->can('Create:SystemBackup'))
                ->requiresConfirmation()
                ->modalHeading('Create a system backup')
                ->modalDescription('Queues a SHA-256 checked archive of PostgreSQL and GxP file disks. This supports recoverability; it does not certify the product. A queue worker must be running.')
                ->schema([
                    Textarea::make('reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    /** @var User $actor */
                    $actor = auth()->user();

                    CreateSystemBackupJob::dispatch($actor->id, trim((string) $data['reason']));

                    Notification::make()
                        ->title('Backup queued')
                        ->body('The archive will appear in this list when the queue worker finishes.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
                ?array $filters,
                ?int $page,
                ?int $recordsPerPage,
            ): LengthAwarePaginator {
                $records = $this->records();
                $page = $page ?? 1;
                $recordsPerPage = $recordsPerPage ?? 10;

                if (filled($sortColumn)) {
                    $records = $records->sortBy(
                        $sortColumn,
                        SORT_REGULAR,
                        $sortDirection === 'desc',
                    );
                }

                return new LengthAwarePaginator(
                    $records->forPage($page, $recordsPerPage),
                    $records->count(),
                    $recordsPerPage,
                    $page,
                );
            })
            ->columns([
                TextColumn::make('backup_uuid')->label('Backup')->copyable(),
                TextColumn::make('created_at')->label('Created (UTC)')->sortable(),
                TextColumn::make('created_by')->label('Created by')->placeholder('Scheduled'),
                TextColumn::make('reason')->limit(40),
                TextColumn::make('database_sha256')->label('Database SHA-256')->limit(16),
            ])
            ->recordActions([
                Action::make('download')
                    ->icon(Heroicon::ArrowDownTray)
                    ->authorize(fn (): bool => (bool) auth()->user()?->can('Restore:SystemBackup'))
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Restore:SystemBackup'))
                    ->action(function (array $record): BinaryFileResponse {
                        /** @var User $actor */
                        $actor = auth()->user();
                        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gxp-backup-'.$record['id'].'.zip';
                        app(SystemBackupService::class)->writeDownloadZip($record['id'], $path, $actor);

                        return response()->download($path, $record['id'].'.zip')->deleteFileAfterSend(true);
                    }),
                Action::make('restore')
                    ->color('danger')
                    ->icon(Heroicon::ArrowPath)
                    ->authorize(fn (): bool => (bool) auth()->user()?->can('Restore:SystemBackup'))
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Restore:SystemBackup'))
                    ->requiresConfirmation()
                    ->modalHeading('Restore this instance')
                    ->modalDescription('Queues a full overwrite of this instance. You will be signed out. A queue worker must be running. Restore drills should use a non-production host when possible.')
                    ->schema([
                        Textarea::make('reason')
                            ->required()
                            ->rows(3),
                        TextInput::make('signature_password')
                            ->label('Electronic signature password')
                            ->password()
                            ->required(),
                    ])
                    ->action(function (array $record, array $data): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        app(ElectronicSignatureAuthenticator::class)->confirm($actor, $data['signature_password'] ?? null);

                        RestoreSystemBackupJob::dispatch(
                            (string) $record['id'],
                            $actor->id,
                            trim((string) $data['reason']),
                        );

                        Notification::make()
                            ->title('Restore queued')
                            ->body('The archive will replace this instance when the queue worker finishes. Sign in again after it completes.')
                            ->success()
                            ->send();

                        if (! app()->runningUnitTests()) {
                            auth()->logout();
                            session()->invalidate();
                            session()->regenerateToken();
                        }
                    }),
            ])
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No system backups yet')
            ->emptyStateDescription('Create a backup to write an integrity-checked archive of PostgreSQL and GxP files.')
            ->emptyStateIcon(Heroicon::OutlinedCircleStack);
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    private function records(): Collection
    {
        return collect(app(SystemBackupService::class)->list())
            ->map(function (array $manifest): array {
                return [
                    'id' => $manifest['backup_uuid'],
                    ...$manifest,
                ];
            })
            ->keyBy('id');
    }
}
