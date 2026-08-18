<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('identity_uuid')
                    ->label('Durable identity'),
                TextEntry::make('department.name')
                    ->label('Department')
                    ->placeholder('-'),
                TextEntry::make('designation.name')
                    ->label('Designation')
                    ->placeholder('-'),
                IconEntry::make('deactivated_at')
                    ->label('Active')
                    ->boolean()
                    ->state(fn ($record): bool => $record->deactivated_at === null),
                TextEntry::make('locked_at')
                    ->label('Locked at')
                    ->placeholder('Not locked'),
                TextEntry::make('last_login_at')
                    ->label('Last login')
                    ->placeholder('-'),
                TextEntry::make('password_changed_at')
                    ->label('Password changed')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
