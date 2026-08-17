<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupplierQualifications\Tables;

use App\Domain\QMS\Enums\SupplierQualificationStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class SupplierQualificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier_number')->label('Supplier Number')->searchable()->sortable(),
                TextColumn::make('legal_name')->searchable()->limit(40),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('risk_level')->badge(),
                TextColumn::make('category')->badge(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—'),
                TextColumn::make('next_review_at')->date()->placeholder('—')->sortable(),
                TextColumn::make('qualification_expires_at')->date()->placeholder('—')->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(SupplierQualificationStatus::class)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ActionGroup::make([ViewAction::make(), EditAction::make()])->icon('heroicon-o-ellipsis-vertical')]);
    }
}
