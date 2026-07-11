<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeGuides;

use App\Filament\Resources\KnowledgeGuides\Pages\CreateKnowledgeGuide;
use App\Filament\Resources\KnowledgeGuides\Pages\EditKnowledgeGuide;
use App\Filament\Resources\KnowledgeGuides\Pages\ListKnowledgeGuides;
use App\Filament\Resources\KnowledgeGuides\Pages\ViewKnowledgeGuide;
use App\Filament\Resources\KnowledgeGuides\Schemas\KnowledgeGuideForm;
use App\Filament\Resources\KnowledgeGuides\Tables\KnowledgeGuidesTable;
use App\Models\KnowledgeGuide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class KnowledgeGuideResource extends Resource
{
    protected static ?string $model = KnowledgeGuide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Help & Knowledge';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Knowledge Library';

    protected static ?string $modelLabel = 'Guide';

    protected static ?string $pluralModelLabel = 'Guides';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return KnowledgeGuideForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KnowledgeGuidesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if ($user !== null && ! $user->can('Update:KnowledgeGuide')) {
            $query->published();
        }

        return $query->ordered();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeGuides::route('/'),
            'create' => CreateKnowledgeGuide::route('/create'),
            'view' => ViewKnowledgeGuide::route('/{record}'),
            'edit' => EditKnowledgeGuide::route('/{record}/edit'),
        ];
    }
}
