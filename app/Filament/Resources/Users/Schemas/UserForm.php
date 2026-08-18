<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Domain\Shared\Support\PasswordRules;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('designation_id')
                    ->label('Designation')
                    ->relationship('designation', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(PasswordRules::required())
                    ->visibleOn('create')
                    ->helperText('Minimum 12 characters with mixed case, a number, and a symbol. The user must keep this password confidential.'),
            ]);
    }
}
