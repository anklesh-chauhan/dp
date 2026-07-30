<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization Identity')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('code')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50),
                            TextInput::make('legal_name')->required()->maxLength(255),
                            TextInput::make('display_name')->maxLength(255),
                            TextInput::make('registration_number')->maxLength(255),
                            TextInput::make('tax_identifier')->maxLength(255),
                            TextInput::make('timezone')->required()->default('Asia/Kolkata'),
                        ]),
                        KeyValue::make('regulatory_identifiers')
                            ->keyLabel('Identifier type')
                            ->valueLabel('Registration / licence number')
                            ->addActionLabel('Add regulatory identifier')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Registered Address and Contact')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('address_line_1')->required(),
                            TextInput::make('address_line_2'),
                            TextInput::make('city')->required(),
                            TextInput::make('state'),
                            TextInput::make('postal_code'),
                            TextInput::make('country_code')->required()->length(2)->helperText('ISO two-letter country code, for example IN.'),
                            TextInput::make('phone')->tel(),
                            TextInput::make('email')->email(),
                            TextInput::make('website')->url(),
                        ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Document Branding')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('organizations/logos')
                            ->visibility('public'),
                        Textarea::make('document_header')->rows(3),
                        Textarea::make('document_footer')->rows(3),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
