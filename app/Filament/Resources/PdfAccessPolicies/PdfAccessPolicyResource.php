<?php

declare(strict_types=1);

namespace App\Filament\Resources\PdfAccessPolicies;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\PdfAccessPolicies\Pages\CreatePdfAccessPolicy;
use App\Filament\Resources\PdfAccessPolicies\Pages\EditPdfAccessPolicy;
use App\Filament\Resources\PdfAccessPolicies\Pages\ListPdfAccessPolicies;
use App\Models\PdfAccessPolicy;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PdfAccessPolicyResource extends Resource
{
    protected static ?string $model = PdfAccessPolicy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'System & Security Configuration';

    protected static ?string $navigationLabel = 'PDF Access Policies';

    protected static ?int $navigationSort = 2010;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Policy')->schema([
                TextInput::make('name')->required()->maxLength(255),
                Textarea::make('description')->columnSpanFull(),
                Select::make('effect')
                    ->options([
                        PdfAccessPolicy::EFFECT_ALLOW => 'Allow',
                        PdfAccessPolicy::EFFECT_DENY => 'Deny',
                    ])
                    ->required()
                    ->default(PdfAccessPolicy::EFFECT_ALLOW),
                TextInput::make('priority')
                    ->numeric()
                    ->minValue(0)
                    ->default(100)
                    ->required()
                    ->helperText('Higher priority is evaluated first. A deny wins when priorities are equal.'),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('can_view')->label('Controls View')->default(true),
                Toggle::make('can_print')->label('Controls Print'),
                Toggle::make('can_download')->label('Controls Download'),
                Toggle::make('is_active')->default(true),
            ])->columns(4),
            Section::make('Scope')->description('Leave a scope empty to match all values.')->schema([
                Select::make('department_id')->relationship('department', 'name')->searchable()->preload(),
                Select::make('document_type_id')->relationship('documentType', 'name')->searchable()->preload(),
                Select::make('document_status_id')->relationship('documentStatus', 'name')->searchable()->preload(),
            ])->columns(3),
            Section::make('Validity')->schema([
                DateTimePicker::make('effective_from')->seconds(false),
                DateTimePicker::make('expires_at')->seconds(false)->after('effective_from'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('effect')->badge()->color(fn (string $state): string => $state === PdfAccessPolicy::EFFECT_DENY ? 'danger' : 'success'),
                TextColumn::make('priority')->sortable(),
                TextColumn::make('roles.name')->label('Roles')->badge(),
                TextColumn::make('department.name')->placeholder('All departments'),
                TextColumn::make('documentType.name')->label('Document Type')->placeholder('All types'),
                IconColumn::make('can_view')->label('View')->boolean(),
                IconColumn::make('can_print')->label('Print')->boolean(),
                IconColumn::make('can_download')->label('Download')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('effect')->options([
                    PdfAccessPolicy::EFFECT_ALLOW => 'Allow',
                    PdfAccessPolicy::EFFECT_DENY => 'Deny',
                ]),
                SelectFilter::make('department_id')->relationship('department', 'name'),
                SelectFilter::make('document_type_id')->relationship('documentType', 'name'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPdfAccessPolicies::route('/'),
            'create' => CreatePdfAccessPolicy::route('/create'),
            'edit' => EditPdfAccessPolicy::route('/{record}/edit'),
        ];
    }
}
