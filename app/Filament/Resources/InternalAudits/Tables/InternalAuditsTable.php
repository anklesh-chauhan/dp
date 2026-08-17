<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalAudits\Tables;

use App\Domain\QMS\Enums\InternalAuditStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class InternalAuditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('audit_number')->label('Audit Number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('leadAuditor.name')->label('Lead')->placeholder('—'),
                TextColumn::make('scheduled_start_at')->date()->placeholder('—')->sortable(),
                TextColumn::make('scheduled_end_at')->date()->placeholder('—')->sortable(),
                TextColumn::make('closed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(InternalAuditStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
