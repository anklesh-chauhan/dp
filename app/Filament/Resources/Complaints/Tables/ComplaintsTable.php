<?php

declare(strict_types=1);

namespace App\Filament\Resources\Complaints\Tables;

use App\Domain\QMS\Enums\ComplaintStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ComplaintsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('complaint_number')->label('Complaint Number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('source')->badge()->toggleable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('response_due_at')->date()->placeholder('—')->sortable(),
                TextColumn::make('closed_at')->dateTime()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(ComplaintStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
