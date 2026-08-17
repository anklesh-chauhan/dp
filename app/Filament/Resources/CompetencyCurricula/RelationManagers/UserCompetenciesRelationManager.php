<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetencyCurricula\RelationManagers;

use App\Domain\QMS\Models\CompetencyCurriculum;
use App\Domain\QMS\Models\UserCompetency;
use App\Domain\QMS\Services\CompetencyService;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

final class UserCompetenciesRelationManager extends RelationManager
{
    protected static string $relationship = 'userCompetencies';

    protected static ?string $title = 'Assigned users';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS)
            && (bool) auth()->user()?->can('View:UserCompetency');
    }

    public function isReadOnly(): bool
    {
        return ! (bool) auth()->user()?->can('Assign:UserCompetency');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('User')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('trained_at')->dateTime()->placeholder('—'),
                TextColumn::make('expires_at')->dateTime()->placeholder('—'),
                TextColumn::make('verifiedBy.name')->label('Verified by')->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Assign user')
                    ->using(function (array $data): UserCompetency {
                        /** @var User $actor */
                        $actor = auth()->user();
                        /** @var CompetencyCurriculum $curriculum */
                        $curriculum = $this->getOwnerRecord();
                        $user = User::query()->findOrFail((int) $data['user_id']);

                        return app(CompetencyService::class)->assignCurriculum($user, $curriculum, $actor);
                    })
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Assign:UserCompetency')
                        || (bool) auth()->user()?->can('Assign:CompetencyCurriculum')),
            ])
            ->recordActions([
                Action::make('verify')
                    ->label('Verify')
                    ->visible(fn (UserCompetency $record): bool => $record->isCurrent()
                        && (bool) auth()->user()?->can('Verify:UserCompetency'))
                    ->action(function (UserCompetency $record): void {
                        /** @var User $actor */
                        $actor = auth()->user();

                        try {
                            app(CompetencyService::class)->verify($record, $actor);
                            Notification::make()->success()->title('Competency verified')->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Could not verify competency')
                                ->body(collect($exception->errors())->flatten()->implode(' '))
                                ->send();
                        }
                    }),
                Action::make('refresh')
                    ->label('Refresh status')
                    ->action(function (UserCompetency $record): void {
                        app(CompetencyService::class)->refreshUserCompetency($record);
                        Notification::make()->success()->title('Competency status refreshed')->send();
                    })
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Update:UserCompetency')
                        || (bool) auth()->user()?->can('Manage:UserCompetency')),
            ]);
    }
}
