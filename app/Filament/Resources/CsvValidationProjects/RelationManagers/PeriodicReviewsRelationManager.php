<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PeriodicReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'periodicReviews';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('review_no')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                DatePicker::make('due_date')->required(),
                DatePicker::make('reviewed_on'),
                KeyValue::make('review_scope')->required()->columnSpanFull(),
                Textarea::make('findings')->columnSpanFull(),
                Textarea::make('validation_conclusion')->columnSpanFull(),
                Checkbox::make('revalidation_required'),
                DatePicker::make('next_review_date'),
                Select::make('reviewed_by')->relationship('reviewer', 'name')->searchable()->preload(),
                Select::make('approved_by')->relationship('approver', 'name')->searchable()->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('review_no')
            ->columns([
                TextColumn::make('review_no')
                    ->searchable(),
                TextColumn::make('due_date')->date(),
                TextColumn::make('reviewed_on')->date(),
                TextColumn::make('validation_conclusion')->limit(50),
                TextColumn::make('revalidation_required')->badge(),
                TextColumn::make('next_review_date')->date(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
