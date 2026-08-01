<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangeControls\Tables;

use App\Domain\QMS\Enums\ChangeControlStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ChangeControlsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('change_number')
                    ->label('Change Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('department.name')->searchable()->sortable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('implementation_due_at')->date()->placeholder('—')->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(
                        collect(ChangeControlStatus::cases())
                            ->mapWithKeys(fn (ChangeControlStatus $status): array => [
                                $status->value => str($status->value)
                                    ->replace('_', ' ')
                                    ->title()
                                    ->toString(),
                            ])
                            ->all(),
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ])->icon('heroicon-o-ellipsis-vertical'),
            ]);
    }
}
