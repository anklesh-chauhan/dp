<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductLicenses\Schemas;

use App\Models\ProductLicense;
use App\Support\Modules\Contracts\ProductLicenseStateResolver;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use JsonException;

final class ProductLicenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('License')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('license_key')
                                    ->label('License ID')
                                    ->copyable(),
                                TextEntry::make('current_state')
                                    ->label('State')
                                    ->state(fn (ProductLicense $record): string => app(ProductLicenseStateResolver::class)->resolve($record)->value)
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'grace' => 'warning',
                                        'expired', 'revoked', 'invalid' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('key_id')
                                    ->label('Issuer Key ID')
                                    ->copyable(),
                                TextEntry::make('enabled_modules')
                                    ->label('Enabled Modules')
                                    ->state(fn (ProductLicense $record): array => self::modules($record))
                                    ->badge()
                                    ->separator(','),
                            ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Lifecycle')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('activated_at')->dateTime()->placeholder('—'),
                                TextEntry::make('issued_at')->dateTime()->placeholder('—'),
                                TextEntry::make('expires_at')->dateTime()->placeholder('—'),
                                TextEntry::make('grace_ends_at')->dateTime()->placeholder('—'),
                                TextEntry::make('revoked_at')->dateTime()->placeholder('—'),
                                TextEntry::make('last_verified_at')->dateTime()->placeholder('—'),
                                TextEntry::make('created_at')->dateTime()->placeholder('—'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return list<string>
     */
    private static function modules(ProductLicense $license): array
    {
        try {
            $claims = json_decode($license->payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        $modules = is_array($claims) ? ($claims['modules'] ?? null) : null;

        if (! is_array($modules)) {
            return [];
        }

        return array_values(array_filter(
            $modules,
            fn (mixed $module): bool => is_string($module),
        ));
    }
}
