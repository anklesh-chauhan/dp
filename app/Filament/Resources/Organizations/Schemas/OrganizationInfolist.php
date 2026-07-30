<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class OrganizationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization')->schema([
                    Grid::make(3)->schema([
                        ImageEntry::make('logo_path')->disk('public')->label('Logo'),
                        TextEntry::make('code'),
                        TextEntry::make('legal_name'),
                        TextEntry::make('display_name')->placeholder('—'),
                        TextEntry::make('registration_number')->placeholder('—'),
                        TextEntry::make('tax_identifier')->placeholder('—'),
                        IconEntry::make('is_default')->boolean(),
                        IconEntry::make('is_active')->boolean(),
                    ]),
                    KeyValueEntry::make('regulatory_identifiers')->columnSpanFull(),
                ])->columnSpanFull(),
                Section::make('Address and Contact')->schema([
                    TextEntry::make('address_line_1'),
                    TextEntry::make('address_line_2')->placeholder('—'),
                    TextEntry::make('city'),
                    TextEntry::make('state')->placeholder('—'),
                    TextEntry::make('postal_code')->placeholder('—'),
                    TextEntry::make('country_code'),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('email')->placeholder('—'),
                    TextEntry::make('website')->placeholder('—'),
                ])->columns(3)->columnSpanFull(),
            ]);
    }
}
