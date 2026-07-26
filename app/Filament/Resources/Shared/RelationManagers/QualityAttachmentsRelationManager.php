<?php

declare(strict_types=1);

namespace App\Filament\Resources\Shared\RelationManagers;

use App\Domain\QMS\Models\QualityAttachment;
use App\Domain\QMS\Services\QualityAttachmentIntegrityService;
use App\Enums\ProductModule;
use App\Support\Modules\ModuleManager;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class QualityAttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Quality Evidence';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS)
            && (bool) auth()->user()?->can('View:QualityAttachment');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('path')
                ->label('Attachment')
                ->disk('local')
                ->directory('qms/quality-attachments')
                ->visibility('private')
                ->preventFilePathTampering()
                ->storeFileNamesIn('original_name')
                ->maxSize(10_240)
                ->required(),
            Textarea::make('description')
                ->maxLength(2_000),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable()
                    ->wrap()
                    ->url(fn ($record): string => Storage::disk($record->disk)->temporaryUrl(
                        $record->path,
                        now()->addMinutes(5),
                    ))
                    ->openUrlInNewTab(),
                TextColumn::make('mime_type')->label('Type')->placeholder('—'),
                TextColumn::make('size_bytes')->label('Bytes')->numeric()->placeholder('—'),
                TextColumn::make('description')->wrap()->placeholder('—'),
                TextColumn::make('integrity_status')
                    ->label('Integrity')
                    ->state(fn (QualityAttachment $record) => app(QualityAttachmentIntegrityService::class)
                        ->status($record))
                    ->badge(),
                TextColumn::make('content_hash')
                    ->label('SHA-256')
                    ->limit(16)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('uploader.name')->label('Uploaded By')->placeholder('System'),
                TextColumn::make('uploaded_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Evidence')
                    ->authorize(fn (): bool => app(ModuleManager::class)->enabled(ProductModule::QMS)
                        && (bool) auth()->user()?->can('Create:QualityAttachment'))
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Create:QualityAttachment'))
                    ->mutateDataUsing(function (array $data): array {
                        app(ModuleManager::class)->ensureEnabled(ProductModule::QMS);

                        if (! auth()->user()?->can('Create:QualityAttachment')) {
                            throw new AuthorizationException('You do not have permission to upload quality evidence.');
                        }

                        $data['disk'] = 'local';
                        $data['uploaded_by'] = auth()->id();
                        $data['uploaded_at'] = now();

                        return $data;
                    }),
            ])
            ->defaultSort('uploaded_at', 'desc');
    }
}
