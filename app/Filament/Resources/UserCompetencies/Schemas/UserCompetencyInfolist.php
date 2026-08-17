<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserCompetencies\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class UserCompetencyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User competency')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('user.name')->label('User'),
                        TextEntry::make('curriculum.code')->label('Curriculum code'),
                        TextEntry::make('curriculum.name')->label('Curriculum'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('trained_at')->dateTime()->placeholder('—'),
                        TextEntry::make('expires_at')->dateTime()->placeholder('—'),
                        TextEntry::make('assignedBy.name')->label('Assigned by')->placeholder('—'),
                        TextEntry::make('assigned_at')->dateTime()->placeholder('—'),
                        TextEntry::make('verifiedBy.name')->label('Verified by')->placeholder('—'),
                        TextEntry::make('verified_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
