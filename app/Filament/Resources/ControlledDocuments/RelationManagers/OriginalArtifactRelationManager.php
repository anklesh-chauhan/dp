<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\RelationManagers;

use App\Models\DocumentOriginalArtifact;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OriginalArtifactRelationManager extends RelationManager
{
    protected static string $relationship = 'originalArtifacts';

    protected static ?string $title = 'Original Imported Files';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->originalArtifacts()->exists();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('original_name')->label('Original File')->searchable()
                ->url(fn (DocumentOriginalArtifact $record): string => strtolower((string) $record->mime_type) === 'application/pdf' || $record->preview_path !== null
                    ? route('controlled-documents.original-artifacts.viewer', [
                        'controlledDocument' => $this->getOwnerRecord(),
                        'artifact' => $record,
                    ])
                    : route('controlled-documents.original-artifacts.view', [
                        'controlledDocument' => $this->getOwnerRecord(),
                        'artifact' => $record,
                    ]))
                ->openUrlInNewTab(),
            TextColumn::make('mime_type')->label('Format')->placeholder('Unknown'),
            TextColumn::make('size_bytes')->label('Size')->numeric()->suffix(' bytes'),
            TextColumn::make('sha256')->label('SHA-256')->limit(16)->copyable(),
            TextColumn::make('uploaded_at')->dateTime()->sortable(),
        ]);
    }
}
