<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangeControls\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ChangeControlInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Change Control')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('change_number')->label('Change Number')->copyable(),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('department.name')->label('Department'),
                            TextEntry::make('title')->columnSpanFull(),
                            TextEntry::make('description')->columnSpanFull(),
                            TextEntry::make('rationale')->columnSpanFull(),
                            TextEntry::make('requester.name')->label('Requested By')->placeholder('—'),
                            TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Lifecycle')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('submitted_at')->dateTime()->placeholder('—'),
                            TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                            TextEntry::make('implementation_due_at')->date()->placeholder('—'),
                            TextEntry::make('implemented_at')->dateTime()->placeholder('—'),
                            TextEntry::make('effectiveness_due_at')->date()->placeholder('—'),
                            TextEntry::make('effectiveness_verified_at')->dateTime()->placeholder('—'),
                            TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
