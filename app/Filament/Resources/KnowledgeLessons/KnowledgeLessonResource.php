<?php

declare(strict_types=1);

namespace App\Filament\Resources\KnowledgeLessons;

use App\Filament\Resources\KnowledgeLessons\Pages\CreateKnowledgeLesson;
use App\Filament\Resources\KnowledgeLessons\Pages\ListKnowledgeLessons;
use App\Filament\Resources\KnowledgeLessons\Pages\ViewKnowledgeLesson;
use App\Models\KnowledgeLesson;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class KnowledgeLessonResource extends Resource
{
    protected static ?string $model = KnowledgeLesson::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'DMS · Help & Knowledge';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Lessons Learned';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('summary')->required()->rows(3)->columnSpanFull(),
            Textarea::make('body')->required()->rows(8)->columnSpanFull(),
            Toggle::make('is_published')->default(true),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('lesson_number'),
            TextEntry::make('title'),
            TextEntry::make('summary')->columnSpanFull(),
            TextEntry::make('body')->markdown()->columnSpanFull(),
            TextEntry::make('creator.name')->label('Recorded by'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lesson_number')->searchable(),
                TextColumn::make('title')->searchable()->limit(40),
                IconColumn::make('is_published')->boolean(),
                TextColumn::make('created_at')->since(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeLessons::route('/'),
            'create' => CreateKnowledgeLesson::route('/create'),
            'view' => ViewKnowledgeLesson::route('/{record}'),
        ];
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
