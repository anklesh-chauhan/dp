<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteMasterFiles\Tables;

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class SiteMasterFilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('smf_number')->label('SMF Number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('version')->sortable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('published_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(SiteMasterFileStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
