<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditFindings\Tables;

use App\Domain\QMS\Enums\AuditFindingDisposition;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class AuditFindingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('finding_number')->label('Finding Number')->searchable()->sortable(),
                TextColumn::make('internalAudit.audit_number')->label('Audit')->searchable(),
                TextColumn::make('title')->limit(40)->searchable(),
                TextColumn::make('disposition')->badge()->sortable(),
                TextColumn::make('severity')->badge(),
                TextColumn::make('classification')->badge(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('response_due_at')->date()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('disposition')->options(AuditFindingDisposition::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
