<?php

declare(strict_types=1);

namespace App\Filament\Resources\RiskAssessments\RelationManagers;

use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\Investigation;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\RiskAssessmentLink;
use App\Domain\QMS\Services\RiskAssessmentLinkService;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class EventLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'links';

    protected static ?string $title = 'Linked Quality Events';

    /** @var array<string, class-string<Model>> */
    private const array LINKABLE_OPTIONS = [
        ChangeControl::class => 'Change Control',
        Deviation::class => 'Deviation',
        Capa::class => 'CAPA',
        Complaint::class => 'Complaint',
        AuditFinding::class => 'Audit Finding',
        CsvValidationProject::class => 'CSV Validation Project',
        Investigation::class => 'Investigation',
    ];

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS)
            && (bool) auth()->user()?->can('View:RiskAssessment');
    }

    public function isReadOnly(): bool
    {
        return ! (bool) auth()->user()?->can('Update:RiskAssessment');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('linkable_type')
                ->label('Record type')
                ->options(self::LINKABLE_OPTIONS)
                ->required()
                ->live(),
            Select::make('linkable_id')
                ->label('Record')
                ->searchable()
                ->preload()
                ->required()
                ->options(function (Get $get): array {
                    $type = $get('linkable_type');

                    if (! is_string($type) || ! isset(self::LINKABLE_OPTIONS[$type])) {
                        return [];
                    }

                    return $type::query()
                        ->latest('id')
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Model $record): array => [
                            $record->getKey() => $this->labelFor($record),
                        ])
                        ->all();
                }),
            TextInput::make('link_role')
                ->label('Link role')
                ->placeholder('source')
                ->maxLength(100),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('linkable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (?string $state): string => self::LINKABLE_OPTIONS[$state] ?? class_basename((string) $state)),
                TextColumn::make('linkable_id')
                    ->label('Record')
                    ->formatStateUsing(function (RiskAssessmentLink $record): string {
                        $linkable = $record->linkable;

                        return $linkable instanceof Model
                            ? $this->labelFor($linkable)
                            : (string) $record->linkable_id;
                    }),
                TextColumn::make('link_role')->placeholder('—'),
                TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Link record')
                    ->using(function (array $data): RiskAssessmentLink {
                        /** @var User $actor */
                        $actor = auth()->user();
                        /** @var RiskAssessment $assessment */
                        $assessment = $this->getOwnerRecord();
                        /** @var class-string<Model> $type */
                        $type = $data['linkable_type'];
                        $linkable = $type::query()->findOrFail((int) $data['linkable_id']);

                        return app(RiskAssessmentLinkService::class)->attach(
                            $assessment,
                            $linkable,
                            $actor,
                            $data['link_role'] ?? null,
                        );
                    })
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Update:RiskAssessment')),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->label('Unlink')
                    ->using(function (RiskAssessmentLink $record): void {
                        /** @var User $actor */
                        $actor = auth()->user();

                        app(RiskAssessmentLinkService::class)->detach($record, $actor);
                    })
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Update:RiskAssessment')),
            ]);
    }

    private function labelFor(Model $record): string
    {
        foreach (['change_number', 'deviation_number', 'capa_number', 'complaint_number', 'finding_number', 'project_number', 'investigation_number', 'title'] as $attribute) {
            if (filled($record->getAttribute($attribute))) {
                $label = (string) $record->getAttribute($attribute);

                if ($attribute !== 'title' && filled($record->getAttribute('title'))) {
                    return $label.' — '.(string) $record->getAttribute('title');
                }

                if ($attribute !== 'title' && filled($record->getAttribute('system_name'))) {
                    return $label.' — '.(string) $record->getAttribute('system_name');
                }

                return $label;
            }
        }

        return class_basename($record).' #'.$record->getKey();
    }
}
