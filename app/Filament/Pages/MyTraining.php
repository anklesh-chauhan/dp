<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\DMS\Actions\CompleteDocumentTrainingAction;
use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Enums\ProductModule;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Support\MyTrainingQueueService;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

final class MyTraining extends Page implements HasTable
{
    use HandlesServiceExceptions;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'TMS';

    protected static ?string $navigationLabel = 'My Training';

    protected static ?string $title = 'My Training';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'my-training';

    protected static string|array $routeMiddleware = ['module:tms'];

    protected string $view = 'filament.pages.my-training';

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::TMS)
            && (bool) auth()->user()?->can('View:MyTraining');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $count = app(MyTrainingQueueService::class)->pendingCountForUser($user);

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TrainingAssignment::query()
                    ->where('source_type', TrainingAssignmentSource::ControlledDocument)
                    ->where('user_id', Auth::id())
                    ->whereNull('completed_at')
                    ->with([
                        'controlledDocument.documentStatus',
                        'controlledDocument.documentType',
                        'controlledDocument.department',
                        'assignedBy',
                    ])
                    ->orderByDesc('assigned_at'),
            )
            ->columns([
                TextColumn::make('controlledDocument.document_number')
                    ->label('Document')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('controlledDocument.title')
                    ->label('Title')
                    ->limit(45)
                    ->searchable(),
                TextColumn::make('controlledDocument.documentType.name')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('controlledDocument.department.name')
                    ->label('Department')
                    ->placeholder('—'),
                TextColumn::make('assignedBy.name')
                    ->label('Assigned by')
                    ->placeholder('—'),
                TextColumn::make('assigned_at')
                    ->label('Assigned')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('viewDocument')
                    ->label('Open document')
                    ->icon(Heroicon::Eye)
                    ->url(fn (TrainingAssignment $record): string => app(MyTrainingQueueService::class)
                        ->documentViewUrl($record->controlledDocument))
                    ->openUrlInNewTab(),
                Action::make('completeTraining')
                    ->label('Complete training')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->schema([
                        Textarea::make('completion_comments')
                            ->label('Read and understood')
                            ->helperText('Confirm that you have read and understood this approved document.')
                            ->rows(3)
                            ->required(),
                    ])
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Complete:TrainingAssignment'))
                    ->action(function (TrainingAssignment $record, array $data): void {
                        $this->runServiceAction(
                            fn () => app(CompleteDocumentTrainingAction::class)->execute(
                                $record,
                                Auth::user(),
                                $data['completion_comments'] ?? null,
                            ),
                            failureTitle: 'Could not complete training',
                            successTitle: 'Training completed',
                        );
                    }),
            ])
            ->emptyStateHeading('No pending training')
            ->emptyStateDescription('Assigned read-and-understand training on approved documents will appear here.')
            ->emptyStateIcon(Heroicon::OutlinedCheckBadge);
    }
}
