<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserCompetencies\Tables;

use App\Domain\TMS\Enums\UserCompetencyStatus;
use App\Domain\TMS\Models\UserCompetency;
use App\Domain\TMS\Services\CompetencyService;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

final class UserCompetenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->searchable()->sortable(),
                TextColumn::make('curriculum.code')->label('Curriculum')->searchable()->sortable(),
                TextColumn::make('curriculum.role_name')->label('Role')->placeholder('—'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('trained_at')->dateTime()->placeholder('—')->sortable(),
                TextColumn::make('expires_at')->dateTime()->placeholder('—')->sortable(),
                TextColumn::make('verifiedBy.name')->label('Verified by')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(UserCompetencyStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
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
                ])->icon('heroicon-o-ellipsis-vertical'),
            ]);
    }
}
