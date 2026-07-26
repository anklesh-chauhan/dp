<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductLicenses\Tables;

use App\Models\ProductLicense;
use App\Support\Modules\Contracts\ProductLicenseStateResolver;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class ProductLicensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_key')
                    ->label('License ID')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('current_state')
                    ->label('State')
                    ->state(fn (ProductLicense $record): string => app(ProductLicenseStateResolver::class)->resolve($record)->value)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'grace' => 'warning',
                        'expired', 'revoked', 'invalid' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('key_id')
                    ->label('Issuer Key ID')
                    ->searchable(),
                TextColumn::make('activated_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('issued_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('grace_ends_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('revoked_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('activated_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
